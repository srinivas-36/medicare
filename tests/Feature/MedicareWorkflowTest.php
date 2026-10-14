<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\DoctorAvailabilityException;
use App\Models\TimeSlot;
use App\Models\User;
use App\Services\AppointmentBooking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class MedicareWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_confirms_a_slice_without_stripe_and_rejects_the_horizon_and_leave(): void
    {
        [$doctorUser, $doctor, $date] = $this->makeDoctor();
        $patient = User::factory()->create(['role' => 'patient', 'is_active' => true]);

        $this->actingAs($patient)
            ->post(route('doctors.book.store', $doctor), $this->slot($date))
            ->assertRedirect();

        $appointment = Appointment::query()->first();
        $this->assertSame('confirmed', $appointment->status);
        $this->assertNull($appointment->payment_status);
        $this->assertNull($appointment->stripe_session_id);
        $this->assertSame('12:00:00', $appointment->ending_time);

        $tooFar = now()->addMonths(7)->toDateString();

        $this->actingAs($patient)
            ->post(route('doctors.book.store', $doctor), $this->slot($tooFar))
            ->assertSessionHasErrors('appointment_date');

        DoctorAvailabilityException::query()->create([
            'doctor_id' => $doctor->id,
            'title' => 'Leave',
            'kind' => 'unavailable',
            'starts_on' => now()->addDays(8)->toDateString(),
            'ends_on' => now()->addDays(8)->toDateString(),
        ]);

        $leave = now()->addDays(8);
        while (strtolower($leave->format('l')) !== strtolower(now()->addDay()->format('l'))) {
            $leave->addDay();
        }

        if ($leave->lte(AppointmentBooking::bookableUntil())) {
            DoctorAvailabilityException::query()->where('doctor_id', $doctor->id)->update([
                'starts_on' => $leave->toDateString(),
                'ends_on' => $leave->toDateString(),
            ]);

            $this->actingAs($patient)
                ->from(route('doctors.book', $doctor))
                ->post(route('doctors.book.store', $doctor), $this->slot($leave->toDateString(), '10:00', '10:30'))
                ->assertSessionHasErrors('starting_time');
        }

        $this->actingAs($patient)
            ->post(route('doctors.book.store', $doctor), $this->slot($date))
            ->assertSessionHasErrors('starting_time');

        $this->assertSame(1, Appointment::query()->count());
        $this->actingAs($doctorUser)->get(route('doctor.availability'))->assertOk()->assertSee('Weekly schedule');
    }

    public function test_patient_history_is_private_and_a_new_visit_does_not_replace_the_old_one(): void
    {
        [, $doctor] = $this->makeDoctor();
        $patient = User::factory()->create(['role' => 'patient', 'name' => 'Amina Rahman']);
        $other = User::factory()->create(['role' => 'patient']);

        $first = Appointment::query()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => now()->subMonths(2)->toDateString(),
            'appointment_time' => '09:00:00',
            'ending_time' => '09:30:00',
            'status' => 'completed',
            'visit_type' => 'consultation',
            'reason' => 'Earlier headache',
        ]);

        Consultation::query()->create([
            'appointment_id' => $first->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'diagnosis' => 'Tension headache',
            'plan' => 'Rest and review.',
        ]);

        $second = Appointment::query()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => now()->subDay()->toDateString(),
            'appointment_time' => '10:00:00',
            'ending_time' => '10:30:00',
            'status' => 'completed',
            'visit_type' => 'follow-up',
            'reason' => 'Return visit',
        ]);

        Consultation::query()->create([
            'appointment_id' => $second->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'diagnosis' => 'Resolved',
            'plan' => 'No further medicine.',
        ]);

        $this->actingAs($patient)
            ->get(route('patient.history'))
            ->assertOk()
            ->assertSee('Earlier headache')
            ->assertSee('Tension headache')
            ->assertSee('Return visit');

        $this->actingAs($patient)
            ->get(route('patient.history.show', $first))
            ->assertOk()
            ->assertSee('Tension headache')
            ->assertDontSee('Resolved');

        $this->assertSame('Tension headache', $first->fresh()->consultation->diagnosis);

        $this->actingAs($other)
            ->get(route('patient.history.show', $first))
            ->assertForbidden();

        $this->actingAs($other)
            ->get(route('patient.history'))
            ->assertOk()
            ->assertDontSee('Earlier headache');
    }

    public function test_admin_approves_a_doctor_and_password_change_requires_the_current_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $doctorUser = User::factory()->create(['role' => 'doctor', 'is_active' => true]);
        $doctor = Doctor::query()->create([
            'user_id' => $doctorUser->id,
            'approval_status' => 'pending',
            'specialization' => 'Cardiology',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.doctors.approve', $doctor))
            ->assertRedirect();

        $this->assertSame('approved', $doctor->fresh()->approval_status);

        $this->actingAs($doctorUser)
            ->put(route('account.password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHasErrors('current_password');

        $this->actingAs($doctorUser)
            ->put(route('account.password.update'), [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertTrue(ClinicSetting::current()->booking_horizon_months >= 1);
    }

    /**
     * @return array{0: User, 1: Doctor, 2: string}
     */
    private function makeDoctor(): array
    {
        $date = now()->addDay()->toDateString();
        $user = User::factory()->create([
            'name' => 'Dr Maya Chen',
            'role' => 'doctor',
            'slug' => 'dr-maya-'.Str::lower(Str::random(6)),
            'is_active' => true,
        ]);
        $doctor = Doctor::query()->create([
            'user_id' => $user->id,
            'specialization' => 'Surgeon',
            'ticket_price' => 150,
            'approval_status' => 'approved',
            'appointment_duration' => 30,
        ]);
        TimeSlot::query()->create([
            'doctor_id' => $doctor->id,
            'day' => strtolower(now()->addDay()->format('l')),
            'starting_time' => '09:00',
            'ending_time' => '12:00',
            'is_available' => true,
        ]);

        return [$user, $doctor, $date];
    }

    /**
     * @return array<string, string>
     */
    private function slot(string $date, string $start = '09:00', string $end = '12:00'): array
    {
        return [
            'appointment_date' => $date,
            'slot_day' => strtolower(Carbon::parse($date)->format('l')),
            'starting_time' => $start,
            'ending_time' => $end,
            'health_issues' => 'Headache',
        ];
    }
}
