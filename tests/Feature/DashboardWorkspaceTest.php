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

        $this->actingAs($doctorUser)
            ->post(route('doctor.patients.store'), [
                'name' => 'Amina Rahman',
                'email' => 'amina@example.com',
                'phone' => '555-0101',
                'age' => 42,
                'date' => $date,
                'time' => '09:30',
                'visit_type' => 'follow-up',
                'reason' => 'Existing visit',
            ])
            ->assertRedirect();

        $this->actingAs($doctorUser)
            ->get(route('doctor.patients.show', $existing))
            ->assertOk()
            ->assertSee('Amina Rahman')
            ->assertSee('Existing visit');
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
            ->assertSee('Book a doctor')
            ->assertSee('Dr Maya Chen')
            ->assertSee('Confirm appointment')
            ->assertSee('name="appointment_date"', false);

        $this->actingAs($patient)
            ->get(route('patient.book'))
            ->assertOk()
            ->assertSee('Confirm appointment')
            ->assertSee('Dr Maya Chen');

        $this->actingAs($patient)
            ->get(route('patient.book.create', $doctor))
            ->assertRedirect(route('patient.book', ['doctor' => $doctor->id]));
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
