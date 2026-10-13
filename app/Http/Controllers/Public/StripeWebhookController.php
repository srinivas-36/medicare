<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\AppointmentBooking;
use App\Services\StripeCheckout;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class StripeWebhookController extends Controller
{
    public function __construct(
        private StripeCheckout $stripe,
        private AppointmentBooking $booking,
    ) {}

    public function __invoke(Request $request): Response
    {
        try {
            $event = $this->stripe->constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature'),
            );
        } catch (\Throwable) {
            return response('Invalid payload', 400);
        }

        $type = (string) data_get($event, 'type');
        $session = data_get($event, 'data.object');

        if ($type === 'checkout.session.completed') {
            $this->markPaid($session);
        }

        if (in_array($type, ['checkout.session.expired', 'checkout.session.async_payment_failed'], true)) {
            $this->markFailed($session);
        }

        return response('ok', 200);
    }

    private function markPaid(mixed $session): void
    {
        $this->withAppointment($session, function (Appointment $appointment) use ($session) {
            if ($appointment->stripe_session_id !== data_get($session, 'id')) {
                return;
            }

            if ($appointment->payment_status === 'paid') {
                return;
            }

            $expected = $this->booking->cents((string) $appointment->ticket_price);
            $amount = (int) data_get($session, 'amount_total');
            $currency = strtolower((string) data_get($session, 'currency'));

            if ($amount !== $expected || $currency !== 'usd') {
                return;
            }

            $intent = data_get($session, 'payment_intent');

            $appointment->forceFill([
                'payment_status' => 'paid',
                'paid_at' => now(),
                'status' => 'confirmed',
                'stripe_payment_intent' => is_string($intent) ? $intent : data_get($intent, 'id'),
            ])->save();
        });
    }

    private function markFailed(mixed $session): void
    {
        $this->withAppointment($session, function (Appointment $appointment) use ($session) {
            if ($appointment->stripe_session_id !== data_get($session, 'id')) {
                return;
            }

            if ($appointment->payment_status !== 'unpaid') {
                return;
            }

            $appointment->forceFill([
                'payment_status' => 'failed',
                'status' => 'cancelled',
            ])->save();
        });
    }

    private function withAppointment(mixed $session, callable $callback): void
    {
        $id = data_get($session, 'client_reference_id');

        if ($id === null || $id === '') {
            return;
        }

        DB::transaction(function () use ($id, $callback) {
            $appointment = Appointment::query()->whereKey($id)->lockForUpdate()->first();

            if ($appointment !== null) {
                $callback($appointment);
            }
        });
    }
}
