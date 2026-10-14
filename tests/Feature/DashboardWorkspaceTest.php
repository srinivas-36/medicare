<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_can_add_a_new_patient_and_an_existing_one(): void
    {
        [$doctorUser] = $this->makeDoctor();
        $existing = User::factory()->create([
            'name' => 'Amina Rahman',
            'role' => 'patient',
            'email' => 'amina@example.com',
        ]);
        $existing->patient_profile()->create(['age' => 42]);

        $date = now()->addDay()->toDateString();

        $this->actingAs($doctorUser)
            ->get(route('doctor.patients.create'))
            ->assertOk()
            ->assertSee('Add a patient')
            ->assertSee('Existing patient')
            ->assertSee('Book appointment');

        $this->actingAs($doctorUser)
            ->post(route('doctor.patients.store'), [
                'name' => 'New Patient',
                'email' => 'new.patient@example.com',
                'phone' => '555-0199',
                'age' => 30,
                'gender' => 'female',
                'blood_type' => 'O+',
                'password' => 'password',
                'password_confirmation' => 'password',
                'date' => $date,
                'time' => '09:00',
                'visit_type' => 'consultation',
                'reason' => 'New visit',
            ])
            ->assertRedirect();

        $created = User::query()->where('email', 'new.patient@example.com')->first();
        $this->assertNotNull($created);
        $this->assertSame('patient', $created->role);
        $this->assertSame(30, $created->patient_profile->age);
        $this->assertTrue(
            Appointment::query()->where('patient_id', $created->id)->where('status', 'confirmed')->exists()
        );

        $this->actingAs($doctorUser)
            ->get(route('doctor.patients'))
            ->assertOk()
            ->assertSee('New Patient')
            ->assertSee('Added by you');

        TimeSlot::query()->create([
            'doctor_id' => $doctorUser->doctor->id,
            'day' => strtolower(now()->addDay()->format('l')),
            'starting_time' => '14:00',
            'ending_time' => '15:00',
            'is_available' => true,
        ]);

        $this->actingAs($doctorUser)
            ->post(route('doctor.patients.store'), [
                'mode' => 'existing',
                'patient_id' => $existing->id,
                'date' => $date,
                'time' => '14:00',
                'visit_type' => 'follow-up',
                'reason' => 'Existing visit',
            ])
            ->assertRedirect();

        $this->actingAs($doctorUser)
            ->get(route('doctor.patients.show', $existing))
            ->assertOk()
            ->assertSee('Amina Rahman')
            ->assertSee('Added by you')
            ->assertSee('Existing visit');
    }

    public function test_doctor_home_shows_the_practice_dashboard(): void
    {
        [$user, $doctor] = $this->makeDoctor();

        TimeSlot::query()->create([
            'doctor_id' => $doctor->id,
            'day' => strtolower(now()->format('l')),
            'starting_time' => '09:00',
            'ending_time' => '11:00',
            'is_available' => true,
        ]);

        $patient = User::factory()->create([
            'name' => 'Srinivas Balam',
            'role' => 'patient',
            'is_active' => true,
        ]);

        $start = now()->copy()->endOfDay()->subMinutes(2);

        Appointment::query()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $start->toDateString(),
            'appointment_time' => $start->format('H:i:s'),
            'ending_time' => $start->copy()->addMinutes(30)->format('H:i:s'),
            'status' => 'confirmed',
            'visit_type' => 'consultation',
            'ticket_price' => 150,
            'payment_status' => 'paid',
        ]);

        $this->actingAs($user)
            ->get(route('doctor.dashboard'))
            ->assertOk()
            ->assertSee("Here's your practice at a glance.", false)
            ->assertSee("Today's visits")
            ->assertSee("Today's Schedule")
            ->assertSee('Next Appointment')
            ->assertSee('Quick Actions')
            ->assertSee('Recent Appointments')
            ->assertSee("Today's Overview", false)
            ->assertSee('Srinivas Balam')
            ->assertSee('Open Appointment Details')
            ->assertSee('Better care brighter tomorrows')
            ->assertSee('Need help?')
            ->assertSee('Consultations')
            ->assertSee('₹150')
            ->assertDontSee('John Smith');

        $this->actingAs($user)
            ->get(route('doctor.dashboard', ['date' => now()->startOfWeek()->subWeek()->toDateString()]))
            ->assertOk()
            ->assertSee('Visits this week')
            ->assertSee('Visit mix')
            ->assertDontSee('No openings on this day.');

        $this->actingAs($user)->get(route('doctor.consultations'))->assertOk()->assertSee('Srinivas Balam');
        $this->actingAs($user)->get(route('doctor.services'))->assertOk()->assertSee('₹150');
        $this->actingAs($user)->get(route('doctor.reports'))->assertOk()->assertSee('No reports have been uploaded.');
        $this->actingAs($user)->get(route('doctor.reviews'))->assertOk()->assertSee('No ratings yet.');
    }

    public function test_patient_books_a_doctor_from_the_dashboard(): void
    {
        [, $doctor] = $this->makeDoctor();
        $patient = User::factory()->create([
            'role' => 'patient',
            'name' => 'Booker Patient',
        ]);

        $this->actingAs($patient)
            ->get(route('patient.dashboard'))
            ->assertOk()
            ->assertSee('Find a Doctor')
            ->assertSee('Next appointment')
            ->assertDontSee('name="appointment_date"', false);

        $this->actingAs($patient)
            ->get(route('patient.book'))
            ->assertOk()
            ->assertSee('Select a doctor')
            ->assertSee('Dr Maya Chen')
            ->assertSee('Select')
            ->assertDontSee('Submit booking');

        $this->actingAs($patient)
            ->get(route('patient.book', ['doctor' => $doctor->id]))
            ->assertRedirect(route('patient.book.create', $doctor));

        $this->actingAs($patient)
            ->get(route('patient.book.create', $doctor))
            ->assertOk()
            ->assertSee('Confirm booking')
            ->assertSee('Available time slots')
            ->assertSee('Medical reports')
            ->assertSee('Submit booking')
            ->assertSee('name="blood_reports[]"', false)
            ->assertSee('name="blood_report_name"', false)
            ->assertSee('name="xray_report_name"', false)
            ->assertSee('name="other_report_name"', false)
            ->assertSee('name="appointment_date"', false);
    }

    public function test_patient_doctor_directory_shows_twelve_doctors_per_page(): void
    {
        $patient = User::factory()->create([
            'role' => 'patient',
        ]);

        foreach (range(1, 13) as $index) {
            $user = User::factory()->create([
                'name' => sprintf('Directory Doctor %02d', $index),
                'role' => 'doctor',
                'is_active' => true,
            ]);

            Doctor::query()->create([
                'user_id' => $user->id,
                'specialization' => 'Cardiology',
                'ticket_price' => 80,
                'approval_status' => 'approved',
            ]);
        }

        $this->actingAs($patient)
            ->get(route('patient.book'))
            ->assertOk()
            ->assertSee('Showing 1–12 of 13 doctors')
            ->assertSee('Directory Doctor 01')
            ->assertSee('Directory Doctor 12')
            ->assertDontSee('Directory Doctor 13');

        $this->actingAs($patient)
            ->get(route('patient.book', ['page' => 2]))
            ->assertOk()
            ->assertSee('Showing 13–13 of 13 doctors')
            ->assertSee('Directory Doctor 13')
            ->assertDontSee('Directory Doctor 01');
    }

    /**
     * @return array{0: User, 1: Doctor}
     */
    private function makeDoctor(): array
    {
        $user = User::factory()->create([
            'name' => 'Dr Maya Chen',
            'role' => 'doctor',
            'slug' => 'dr-maya-chen-'.Str::lower(Str::random(8)),
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
            'day' => strtolower(now()->addDay()->format('l')),
            'starting_time' => '09:00',
            'ending_time' => '12:00',
            'is_available' => true,
        ]);

        return [$user, $doctor];
    }
}
