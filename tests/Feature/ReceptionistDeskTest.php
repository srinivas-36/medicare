<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReceptionistDeskTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_the_doctor_can_add_and_remove_a_receptionist(): void
    {
        [$doctorUser, $doctor] = $this->makeDoctor();
        [$otherUser] = $this->makeDoctor('Dr Other Clinic', 'other-clinic');
        $patient = User::factory()->create(['role' => 'patient']);

        $this->actingAs($patient)
            ->get(route('doctor.receptionists'))
            ->assertForbidden();

        $this->actingAs($doctorUser)
            ->get(route('doctor.receptionists'))
            ->assertOk()
            ->assertSee('Your receptionists')
            ->assertSee('No receptionists yet');

        $this->actingAs($doctorUser)
            ->get(route('doctor.receptionists.create'))
            ->assertOk()
            ->assertSee('Add a receptionist')
            ->assertSee('What they can do');

        $this->actingAs($doctorUser)
            ->post(route('doctor.receptionists.store'), [
                'name' => 'Nora Desk',
                'email' => 'nora.desk@example.com',
                'phone' => '555-0144',
                'gender' => 'female',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertRedirect(route('doctor.receptionists'))
            ->assertSessionHas('status');

        $receptionist = User::query()->where('email', 'nora.desk@example.com')->first();

        $this->assertNotNull($receptionist);
        $this->assertSame('receptionist', $receptionist->role);
        $this->assertSame($doctor->id, $receptionist->receptionist?->doctor_id);

        $this->actingAs($doctorUser)
            ->get(route('doctor.receptionists'))
            ->assertOk()
            ->assertSee('Nora Desk')
            ->assertSee('nora.desk@example.com');

        $this->actingAs($otherUser)
            ->delete(route('doctor.receptionists.destroy', $receptionist))
            ->assertNotFound();

        $this->assertDatabaseHas('users', ['id' => $receptionist->id]);

        $this->actingAs($doctorUser)
            ->delete(route('doctor.receptionists.destroy', $receptionist))
            ->assertRedirect(route('doctor.receptionists'));

        $this->assertDatabaseMissing('users', ['id' => $receptionist->id]);
    }

    public function test_receptionist_books_only_the_assigned_doctors_open_times(): void
    {
        [, $doctor, $date] = $this->makeDoctor();
        [, $other, $otherDate] = $this->makeDoctor('Dr Other Clinic', 'other-clinic');
        $patient = User::factory()->create([
            'name' => 'Front Desk Patient',
            'role' => 'patient',
            'email' => 'front.patient@example.com',
        ]);
        $otherPatient = User::factory()->create([
            'name' => 'Other Clinic Patient',
            'role' => 'patient',
        ]);
        $receptionist = User::factory()->create([
            'name' => 'Nora Desk',
            'role' => 'receptionist',
        ]);
        $receptionist->receptionist()->create([
            'doctor_id' => $doctor->id,
        ]);
        $unassigned = User::factory()->create(['role' => 'receptionist']);

        Appointment::query()->create([
            'patient_id' => $otherPatient->id,
            'doctor_id' => $other->id,
            'appointment_date' => today()->toDateString(),
            'appointment_time' => '10:00:00',
            'status' => 'confirmed',
            'visit_type' => 'consultation',
        ]);

        $this->actingAs($unassigned)
            ->get(route('receptionist.dashboard'))
            ->assertOk()
            ->assertSee('No doctor assigned');

        $this->actingAs($unassigned)
            ->post(route('receptionist.appointments.store'), [
                'patient_id' => $patient->id,
                'date' => $date,
                'slot' => '09:00|12:00',
                'visit_type' => 'check-up',
            ])
            ->assertForbidden();

        $this->actingAs($receptionist)
            ->get(route('receptionist.appointments.create', ['date' => $date, 'q' => 'Front']))
            ->assertOk()
            ->assertSee('Dr Maya Chen')
            ->assertSee('Available time slots')
            ->assertSee('9:00 AM – 12:00 PM')
            ->assertDontSee('9:30 AM')
            ->assertSee('Front Desk Patient')
            ->assertDontSee('Dr Other Clinic');

        $this->actingAs($receptionist)
            ->post(route('receptionist.appointments.store'), [
                'doctor_id' => $other->id,
                'patient_id' => $patient->id,
                'date' => $otherDate,
                'slot' => '09:00|12:00',
                'visit_type' => 'check-up',
            ])
            ->assertForbidden();

        $this->actingAs($receptionist)
            ->post(route('receptionist.appointments.store'), [
                'patient_id' => $patient->id,
                'date' => $date,
                'slot' => '09:00|12:00',
                'reason' => 'Desk booking',
                'visit_type' => 'check-up',
            ])
            ->assertRedirect(route('receptionist.appointments'))
            ->assertSessionHas('status');

        $appointment = Appointment::query()->where('doctor_id', $doctor->id)->first();

        $this->assertNotNull($appointment);
        $this->assertSame('confirmed', $appointment->status);
        $this->assertSame('09:00:00', $appointment->appointment_time);
        $this->assertSame('12:00:00', $appointment->ending_time);
        $this->assertSame($patient->id, $appointment->patient_id);

        $this->actingAs($receptionist)
            ->post(route('receptionist.appointments.store'), [
                'patient_id' => $patient->id,
                'date' => $date,
                'slot' => '09:00|12:00',
                'visit_type' => 'check-up',
            ])
            ->assertSessionHasErrors('starting_time');

        $this->actingAs($receptionist)
            ->get(route('receptionist.dashboard'))
            ->assertOk()
            ->assertSee('Dr Maya Chen')
            ->assertDontSee('Other Clinic Patient');

        $this->actingAs($receptionist)
            ->get(route('receptionist.appointments', ['range' => 'all']))
            ->assertOk()
            ->assertSee('Front Desk Patient')
            ->assertDontSee('Other Clinic Patient');

        $otherAppointment = Appointment::query()->where('doctor_id', $other->id)->first();

        $this->actingAs($receptionist)
            ->post(route('receptionist.appointments.update-status', $otherAppointment), [
                'status' => 'cancelled',
                'cancel_reason' => 'Wrong desk',
            ])
            ->assertForbidden();

        $this->assertSame('confirmed', $otherAppointment->fresh()->status);
    }

    public function test_receptionist_sees_the_assigned_doctors_patients(): void
    {
        [, $doctor] = $this->makeDoctor();
        [, $other] = $this->makeDoctor('Dr Other Clinic', 'other-clinic');
        $own = User::factory()->create([
            'name' => 'Front Desk Patient',
            'role' => 'patient',
            'email' => 'front.patient@example.com',
            'phone' => '555-0100',
        ]);
        $visited = User::factory()->create([
            'name' => 'Return Visit',
            'role' => 'patient',
            'email' => 'return.visit@example.com',
        ]);
        $stranger = User::factory()->create([
            'name' => 'Other Clinic Patient',
            'role' => 'patient',
            'email' => 'other.clinic@example.com',
        ]);
        $doctor->registeredPatients()->attach($own->id);
        Appointment::query()->create([
            'patient_id' => $visited->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => today()->toDateString(),
            'appointment_time' => '10:00:00',
            'status' => 'confirmed',
            'visit_type' => 'consultation',
        ]);
        Appointment::query()->create([
            'patient_id' => $stranger->id,
            'doctor_id' => $other->id,
            'appointment_date' => today()->toDateString(),
            'appointment_time' => '11:00:00',
            'status' => 'confirmed',
            'visit_type' => 'consultation',
        ]);
        $receptionist = User::factory()->create(['role' => 'receptionist']);
        $receptionist->receptionist()->create(['doctor_id' => $doctor->id]);

        $this->actingAs($receptionist)
            ->get(route('receptionist.patients'))
            ->assertOk()
            ->assertSee('Front Desk Patient')
            ->assertSee('front.patient@example.com')
            ->assertSee('Return Visit')
            ->assertDontSee('Other Clinic Patient');

        $this->actingAs($receptionist)
            ->get(route('receptionist.patients', ['q' => 'Return']))
            ->assertOk()
            ->assertSee('Return Visit')
            ->assertDontSee('Front Desk Patient');
    }

    public function test_receptionist_patient_search_stays_on_the_desk(): void
    {
        [, $doctor] = $this->makeDoctor();
        User::factory()->create([
            'name' => 'Front Desk Patient',
            'role' => 'patient',
            'email' => 'front.patient@example.com',
            'phone' => '555-0100',
        ]);
        User::factory()->create([
            'name' => 'Other Clinic Patient',
            'role' => 'patient',
        ]);
        $receptionist = User::factory()->create(['role' => 'receptionist']);
        $receptionist->receptionist()->create(['doctor_id' => $doctor->id]);

        $this->actingAs($receptionist)
            ->getJson(route('receptionist.patients.search', ['q' => 'Front']))
            ->assertOk()
            ->assertJsonPath('patients.0.name', 'Front Desk Patient')
            ->assertJsonPath('patients.0.email', 'front.patient@example.com')
            ->assertJsonMissing(['name' => 'Other Clinic Patient']);

        $this->actingAs($receptionist)
            ->getJson(route('receptionist.patients.search', ['q' => 'F']))
            ->assertOk()
            ->assertJsonPath('patients', []);
    }

    public function test_assigned_receptionist_is_notified_when_a_booking_succeeds(): void
    {
        [$doctorUser, $doctor, $date] = $this->makeDoctor();
        [, $otherDoctor] = $this->makeDoctor('Dr Other Clinic', 'other-clinic');
        $patient = User::factory()->create([
            'name' => 'Front Desk Patient',
            'role' => 'patient',
            'is_active' => true,
        ]);
        $receptionist = User::factory()->create([
            'name' => 'Nora Desk',
            'role' => 'receptionist',
            'is_active' => true,
        ]);
        $receptionist->receptionist()->create(['doctor_id' => $doctor->id]);
        $otherDesk = User::factory()->create([
            'role' => 'receptionist',
            'is_active' => true,
        ]);
        $otherDesk->receptionist()->create(['doctor_id' => $otherDoctor->id]);

        $this->actingAs($patient)
            ->post(route('doctors.book.store', $doctor), [
                'appointment_date' => $date,
                'slot_day' => strtolower(now()->addDay()->format('l')),
                'starting_time' => '09:00',
                'ending_time' => '12:00',
                'health_issues' => 'Headache',
            ])
            ->assertRedirect();

        $appointment = Appointment::query()->where('doctor_id', $doctor->id)->first();
        $this->assertNotNull($appointment);

        $notice = $receptionist->fresh()->notifications()->first();
        $this->assertNotNull($notice);
        $this->assertSame('New appointment', $notice->data['title']);
        $this->assertStringContainsString('Front Desk Patient', $notice->data['body']);
        $this->assertSame(route('receptionist.appointments.show', $appointment), $notice->data['url']);
        $this->assertSame(0, $otherDesk->fresh()->notifications()->count());
        $this->assertSame(1, $doctorUser->fresh()->notifications()->count());

        $this->actingAs($receptionist)
            ->get(route('account.notifications'))
            ->assertOk()
            ->assertSee('New appointment')
            ->assertSee('Front Desk Patient');

        $this->actingAs($receptionist)
            ->get(route('account.notifications.read', $notice->id))
            ->assertRedirect(route('receptionist.appointments.show', $appointment));
    }

    public function test_doctor_sees_whether_the_patient_booked_or_who_added_them(): void
    {
        [$doctorUser, $doctor, $date] = $this->makeDoctor();
        $later = now()->addDays(2)->toDateString();
        $after = now()->addDays(3)->toDateString();
        $followUp = now()->addDays(4)->toDateString();

        foreach ([2, 3, 4] as $offset) {
            TimeSlot::query()->create([
                'doctor_id' => $doctor->id,
                'day' => strtolower(now()->addDays($offset)->format('l')),
                'starting_time' => '09:00',
                'ending_time' => '12:00',
                'is_available' => true,
            ]);
        }

        $booker = User::factory()->create([
            'name' => 'Self Booker',
            'role' => 'patient',
            'is_active' => true,
        ]);
        $known = User::factory()->create([
            'name' => 'Known Patient',
            'role' => 'patient',
            'is_active' => true,
        ]);
        $receptionist = User::factory()->create([
            'name' => 'Nora Desk',
            'role' => 'receptionist',
            'is_active' => true,
        ]);
        $receptionist->receptionist()->create(['doctor_id' => $doctor->id]);

        $this->actingAs($booker)
            ->post(route('doctors.book.store', $doctor), [
                'appointment_date' => $date,
                'slot_day' => strtolower(now()->addDay()->format('l')),
                'starting_time' => '09:00',
                'ending_time' => '12:00',
                'health_issues' => 'Headache',
            ])
            ->assertRedirect();

        $this->actingAs($doctorUser)
            ->get(route('doctor.patients'))
            ->assertOk()
            ->assertSee('Self Booker')
            ->assertSee('Booked by the patient');

        $booked = Appointment::query()->where('patient_id', $booker->id)->first();
        $this->actingAs($doctorUser)
            ->get(route('doctor.appointments.show', $booked))
            ->assertOk()
            ->assertSee('Booked by the patient');

        $this->actingAs($receptionist)
            ->get(route('receptionist.appointments.create', ['mode' => 'new']))
            ->assertOk()
            ->assertSee('New patient')
            ->assertSee('pattern="[0-9]{2}"', false);

        $this->actingAs($receptionist)
            ->post(route('receptionist.appointments.store'), [
                'patient_mode' => 'new',
                'name' => 'Walk In Patient',
                'email' => 'walk.in@example.com',
                'phone' => '555-0177',
                'age' => '2a',
                'gender' => 'female',
                'password' => 'password',
                'password_confirmation' => 'password',
                'date' => $later,
                'slot' => '09:00|12:00',
                'visit_type' => 'consultation',
            ])
            ->assertSessionHasErrors('age');

        $this->actingAs($receptionist)
            ->post(route('receptionist.appointments.store'), [
                'patient_mode' => 'new',
                'name' => 'Walk In Patient',
                'email' => 'walk.in@example.com',
                'phone' => '555-0177',
                'age' => '29',
                'gender' => 'female',
                'password' => 'password',
                'password_confirmation' => 'password',
                'date' => $later,
                'slot' => '09:00|12:00',
                'visit_type' => 'consultation',
                'reason' => 'Front desk add',
            ])
            ->assertRedirect(route('receptionist.appointments'));

        $this->actingAs($receptionist)
            ->post(route('receptionist.appointments.store'), [
                'patient_mode' => 'existing',
                'patient_id' => $known->id,
                'date' => $after,
                'slot' => '09:00|12:00',
                'visit_type' => 'check-up',
            ])
            ->assertRedirect(route('receptionist.appointments'));

        $this->actingAs($doctorUser)
            ->get(route('doctor.patients'))
            ->assertOk()
            ->assertSee('Walk In Patient')
            ->assertSee('Known Patient')
            ->assertSee('Added by Nora Desk')
            ->assertSee('Booked by the patient');

        $walkIn = User::query()->where('email', 'walk.in@example.com')->first();
        $this->actingAs($doctorUser)
            ->get(route('doctor.patients.show', $walkIn))
            ->assertOk()
            ->assertSee('Added by Nora Desk');

        $this->actingAs($receptionist)
            ->post(route('receptionist.appointments.store'), [
                'patient_id' => $booker->id,
                'date' => $followUp,
                'slot' => '09:00|12:00',
                'visit_type' => 'follow-up',
            ])
            ->assertRedirect(route('receptionist.appointments'));

        $this->actingAs($doctorUser)
            ->get(route('doctor.patients.show', $booker))
            ->assertOk()
            ->assertSee('Booked by the patient')
            ->assertDontSee('Added by Nora Desk');
    }

    public function test_receptionist_calendar_view_checks_in_and_reschedules_only_after_the_booked_date(): void
    {
        [, $doctor, $date] = $this->makeDoctor();
        $patient = User::factory()->create([
            'name' => 'Front Desk Patient',
            'role' => 'patient',
            'phone' => '555-0100',
        ]);
        $receptionist = User::factory()->create(['role' => 'receptionist']);
        $receptionist->receptionist()->create(['doctor_id' => $doctor->id]);

        $appointment = Appointment::query()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $date,
            'appointment_time' => '09:00:00',
            'ending_time' => '12:00:00',
            'status' => 'confirmed',
            'visit_type' => 'consultation',
        ]);

        $this->actingAs($receptionist)
            ->get(route('receptionist.appointments', ['range' => 'upcoming']))
            ->assertOk()
            ->assertSee('Calendar')
            ->assertSee('Existing booking')
            ->assertSee('bg-[#16A34A]', false)
            ->assertSee('View · Front Desk Patient', false)
            ->assertSee(route('receptionist.appointments.show', $appointment), false);

        $this->actingAs($receptionist)
            ->get(route('receptionist.appointments.show', $appointment))
            ->assertOk()
            ->assertSee('Check in')
            ->assertSee('Reschedule')
            ->assertSee('Existing booking')
            ->assertSee('data-booked-date="'.$date.'"', false)
            ->assertSee('Choose a day after the booked date.')
            ->assertDontSee('"'.$date.'":', false);

        $this->actingAs($receptionist)
            ->post(route('receptionist.appointments.reschedule', $appointment), [
                'slot' => $date.'|09:00',
            ])
            ->assertSessionHasErrors('date');

        $this->actingAs($receptionist)
            ->post(route('receptionist.appointments.reschedule', $appointment), [
                'date' => now()->toDateString(),
                'time' => '09:00',
            ])
            ->assertSessionHasErrors('date');

        $this->assertSame($date, $appointment->fresh()->appointment_date->toDateString());

        $later = now()->addDay()->addWeek()->toDateString();

        $this->actingAs($receptionist)
            ->post(route('receptionist.appointments.reschedule', $appointment), [
                'slot' => $later.'|09:00',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame($later, $appointment->fresh()->appointment_date->toDateString());
        $this->assertSame('09:00:00', $appointment->fresh()->appointment_time);

        $this->actingAs($receptionist)
            ->post(route('receptionist.appointments.update-status', $appointment), [
                'status' => 'checked_in',
            ])
            ->assertRedirect();

        $this->assertSame('checked_in', $appointment->fresh()->status);

        $bookedOn = $appointment->fresh()->appointment_date->toDateString();
        $afterBooked = $appointment->appointment_date->copy()->addWeek()->toDateString();

        foreach (['checked_in', 'completed', 'cancelled'] as $status) {
            $appointment->forceFill(['status' => $status])->save();

            $this->actingAs($receptionist)
                ->get(route('receptionist.appointments.show', $appointment))
                ->assertOk()
                ->assertDontSee('Reschedule')
                ->assertDontSee('data-reschedule', false);

            $this->actingAs($receptionist)
                ->post(route('receptionist.appointments.reschedule', $appointment), [
                    'slot' => $afterBooked.'|09:00',
                ])
                ->assertSessionHasErrors('date');

            $this->assertSame($bookedOn, $appointment->fresh()->appointment_date->toDateString());
        }
    }

    /**
     * @return array{0: User, 1: Doctor, 2: string}
     */
    private function makeDoctor(string $name = 'Dr Maya Chen', string $slug = 'maya-chen'): array
    {
        $date = now()->addDay()->toDateString();
        $weekday = strtolower(now()->addDay()->format('l'));

        $user = User::factory()->create([
            'name' => $name,
            'role' => 'doctor',
            'slug' => $slug.'-'.Str::lower(Str::random(6)),
            'is_active' => true,
        ]);

        $doctor = Doctor::query()->create([
            'user_id' => $user->id,
            'specialization' => 'Surgeon',
            'ticket_price' => 150,
            'approval_status' => 'approved',
        ]);

        TimeSlot::query()->create([
            'doctor_id' => $doctor->id,
            'day' => $weekday,
            'starting_time' => '09:00',
            'ending_time' => '12:00',
            'is_available' => true,
        ]);

        return [$user->fresh(), $doctor->fresh(), $date];
    }
}
