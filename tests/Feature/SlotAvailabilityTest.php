<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorAvailabilityException;
use App\Models\TimeSlot;
use App\Models\User;
use App\Services\AppointmentBooking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SlotAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_open_slots_are_the_doctors_saved_time_slot_rows(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-13 08:00:00', 'UTC'));

        $maya = $this->doctor('Maya Chen', [
            ['09:00', '12:00'],
            ['14:00', '15:00'],
        ]);
        $other = $this->doctor('Omar Shah', [
            ['16:00', '17:00'],
        ]);

        $booking = app(AppointmentBooking::class);

        $this->assertSame([
            ['start' => '09:00', 'end' => '12:00'],
            ['start' => '14:00', 'end' => '15:00'],
        ], $booking->openSlots($maya, Carbon::parse('2026-10-14')));

        $this->assertSame([
            ['start' => '16:00', 'end' => '17:00'],
        ], $booking->openSlots($other, Carbon::parse('2026-10-14')));

        $this->assertSame([], $booking->openSlots($maya, Carbon::parse('2026-10-15')));
        $this->assertNotContains(['start' => '16:00', 'end' => '17:00'], $booking->openSlots($maya, Carbon::parse('2026-10-14')));
    }

    public function test_a_saved_slot_is_hidden_when_it_has_started_or_overlaps_leave(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-14 10:10:00', 'UTC'));

        $doctor = $this->doctor('Maya Chen', [
            ['09:00', '12:00'],
            ['14:00', '15:00'],
        ]);
        $booking = app(AppointmentBooking::class);

        $this->assertSame([
            ['start' => '14:00', 'end' => '15:00'],
        ], $booking->openSlots($doctor, Carbon::parse('2026-10-14')));

        DoctorAvailabilityException::query()->create([
            'doctor_id' => $doctor->id,
            'title' => 'Meeting',
            'kind' => 'unavailable',
            'starts_on' => '2026-10-14',
            'ends_on' => '2026-10-14',
            'starts_at' => '14:00:00',
            'ends_at' => '14:30:00',
        ]);

        $this->assertSame([], $booking->openSlots($doctor->fresh(), Carbon::parse('2026-10-14')));
    }

    public function test_booked_and_forged_slots_are_rejected_and_reschedule_releases_the_saved_slot(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-13 08:00:00', 'UTC'));

        $doctorUser = User::factory()->create(['role' => 'doctor', 'is_active' => true]);
        $doctor = $this->attachDoctor($doctorUser, [
            ['09:00', '12:00'],
            ['14:00', '15:00'],
        ]);
        $patient = User::factory()->create(['role' => 'patient', 'is_active' => true]);
        $second = User::factory()->create(['role' => 'patient', 'is_active' => true]);
        $date = '2026-10-14';

        $this->actingAs($patient)
            ->post(route('doctors.book.store', $doctor), $this->payload($date, '10:00', '10:30'))
            ->assertSessionHasErrors('starting_time');

        $this->actingAs($patient)
            ->post(route('doctors.book.store', $doctor), $this->payload($date))
            ->assertRedirect();

        $this->assertSame('12:00:00', Appointment::query()->first()->ending_time);

        $this->actingAs($second)
            ->post(route('doctors.book.store', $doctor), $this->payload($date))
            ->assertSessionHasErrors('starting_time');

        $this->actingAs($second)
            ->post(route('doctors.book.store', $doctor), $this->payload($date, '14:00', '15:00'))
            ->assertRedirect();

        $first = Appointment::query()->where('patient_id', $patient->id)->first();

        $this->actingAs($patient)
            ->get(route('patient.bookings.show', $first))
            ->assertOk()
            ->assertSee('Appointment details')
            ->assertSee('Available dates')
            ->assertSee('Available time slots')
            ->assertSee('id="reschedule-open-slots"', false);

        $this->actingAs($patient)
            ->post(route('patient.bookings.reschedule', $first), [
                'slot' => $date.'|14:00',
            ])
            ->assertSessionHasErrors('time');

        $this->actingAs($second)
            ->post(route('patient.bookings.cancel', Appointment::query()->where('patient_id', $second->id)->first()))
            ->assertRedirect();

        $this->actingAs($patient)
            ->post(route('patient.bookings.reschedule', $first), [
                'slot' => $date.'|14:00',
            ])
            ->assertRedirect();

        $this->assertSame('14:00:00', $first->fresh()->appointment_time);
        $this->assertSame('15:00:00', $first->fresh()->ending_time);

        $this->actingAs($second)
            ->post(route('doctors.book.store', $doctor), $this->payload($date))
            ->assertRedirect();
    }

    /**
     * @param  list<array{0: string, 1: string}>  $ranges
     */
    private function doctor(string $name, array $ranges): Doctor
    {
        $user = User::factory()->create([
            'name' => $name,
            'role' => 'doctor',
            'is_active' => true,
        ]);

        return $this->attachDoctor($user, $ranges);
    }

    /**
     * @param  list<array{0: string, 1: string}>  $ranges
     */
    private function attachDoctor(User $user, array $ranges): Doctor
    {
        $doctor = Doctor::query()->create([
            'user_id' => $user->id,
            'specialization' => 'General medicine',
            'ticket_price' => 0,
            'approval_status' => 'approved',
            'appointment_duration' => 30,
            'appointment_buffer' => 0,
        ]);

        foreach ($ranges as [$start, $end]) {
            TimeSlot::query()->create([
                'doctor_id' => $doctor->id,
                'day' => 'wednesday',
                'starting_time' => $start,
                'ending_time' => $end,
                'is_available' => true,
            ]);
        }

        return $doctor->fresh();
    }

    /**
     * @return array<string, string>
     */
    private function payload(string $date, string $start = '09:00', string $end = '12:00'): array
    {
        return [
            'appointment_date' => $date,
            'slot_day' => 'wednesday',
            'starting_time' => $start,
            'ending_time' => $end,
            'health_issues' => 'Checkup',
        ];
    }
}
