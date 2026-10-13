<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeCheckout
{
    public function createSession(Appointment $appointment, Doctor $doctor, string $successUrl, string $cancelUrl): object
    {
        $doctor->loadMissing('user');

        $stripe = new StripeClient((string) config('services.stripe.secret'));
        $cents = app(AppointmentBooking::class)->cents((string) $appointment->ticket_price);

        return $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'customer_email' => $appointment->patient?->email,
            'client_reference_id' => (string) $appointment->id,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => $cents,
                    'product_data' => [
                        'name' => $doctor->user?->name ?: 'Doctor appointment',
                        'description' => mb_substr((string) ($doctor->bio ?: ($doctor->specialization ?: 'Consultation')), 0, 500),
                    ],
                ],
            ]],
        ]);
    }

    public function constructEvent(string $payload, ?string $signature): object
    {
        return Webhook::constructEvent(
            $payload,
            (string) $signature,
            (string) config('services.stripe.webhook_secret'),
        );
    }
}
