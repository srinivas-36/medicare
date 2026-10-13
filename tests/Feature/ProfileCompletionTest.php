<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\DoctorQualification;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_saves_profile_details_without_changing_approval(): void
    {
        $user = User::factory()->create([
            'role' => 'doctor',
            'slug' => 'dr-ada',
        ]);

        $doctor = Doctor::query()->create([
            'user_id' => $user->id,
            'approval_status' => 'pending',
            'ticket_price' => 0,
        ]);

        DoctorQualification::query()->create([
            'doctor_id' => $doctor->id,
            'degree' => 'Old Degree',
            'institution' => 'Old College',
        ]);

        $this->actingAs($user)
            ->put(route('doctor.profile.update'), [
                'name' => 'Dr Ada Lovelace',
                'phone' => '5551234',
                'gender' => 'female',
                'specialization' => 'Surgeon',
                'ticketPrice' => 200,
                'bio' => 'Careful hands.',
                'about' => 'About the practice.',
                'approval_status' => 'approved',
                'qualifications' => [[
                    'degree' => 'MBBS',
                    'university' => 'City College',
                    'startingDate' => '2010-01-01',
                    'endingDate' => '2015-01-01',
                ]],
                'experiences' => [[
                    'position' => 'Consultant',
                    'hospital' => 'General Hospital',
                    'startingDate' => '2016-02-01',
                    'endingDate' => '',
                ]],
                'timeSlots' => [[
                    'day' => 'monday',
                    'startingTime' => '09:00',
                    'endingTime' => '12:00',
                    'isAvailable' => '1',
                ]],
            ])
            ->assertRedirect(route('doctor.profile.show'))
            ->assertSessionHas('status', 'Profile saved.');

        $doctor->refresh();

        $this->assertSame('pending', $doctor->approval_status);
        $this->assertSame('Surgeon', $doctor->specialization);
        $this->assertSame('200.00', $doctor->ticket_price);
        $this->assertDatabaseMissing('doctor_qualifications', ['degree' => 'Old Degree']);
        $this->assertDatabaseHas('doctor_qualifications', [
            'doctor_id' => $doctor->id,
            'degree' => 'MBBS',
            'institution' => 'City College',
        ]);
        $this->assertDatabaseHas('doctor_experiences', [
            'doctor_id' => $doctor->id,
            'designation' => 'Consultant',
            'hospital_name' => 'General Hospital',
            'is_current' => true,
        ]);
        $this->assertTrue(
            TimeSlot::query()->where('doctor_id', $doctor->id)->where('day', 'monday')->where('is_available', true)->exists()
        );

        $this->actingAs($user)
            ->from(route('doctor.profile.edit'))
            ->put(route('doctor.profile.update'), [
                'name' => 'Dr Ada Lovelace',
                'ticketPrice' => 200,
                'timeSlots' => [[
                    'day' => 'monday',
                    'startingTime' => '15:00',
                    'endingTime' => '09:00',
                    'isAvailable' => '1',
                ]],
            ])
            ->assertSessionHasErrors('timeSlots.0.endingTime');

        $this->assertDatabaseHas('doctor_qualifications', ['degree' => 'MBBS']);

        $this->actingAs($user)
            ->get(route('doctor.profile.show'))
            ->assertOk()
            ->assertSee('MBBS')
            ->assertSee('Pending approval');
    }

    public function test_patient_saves_profile_details(): void
    {
        $user = User::factory()->create([
            'role' => 'patient',
            'email' => 'pat@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('patient.profile.edit'))
            ->assertOk();

        $this->actingAs($user)
            ->put(route('patient.profile.update'), [
                'name' => 'Pat Example',
                'phone' => '5559999',
                'age' => 34,
                'gender' => 'other',
                'bloodType' => 'O+',
                'bp' => ['known' => '1', 'value' => '120/80'],
                'diabetic' => ['known' => '0', 'value' => ''],
                'hyperthyroidism' => ['known' => '1', 'value' => 'no'],
            ])
            ->assertRedirect(route('patient.profile.show'));

        $this->assertDatabaseHas('patient_profiles', [
            'user_id' => $user->id,
            'age' => 34,
            'blood_type' => 'O+',
        ]);

        $this->actingAs($user)
            ->get(route('patient.profile.show'))
            ->assertOk()
            ->assertSee('Pat Example')
            ->assertSee('O+')
            ->assertSee('120/80')
            ->assertSee('Not known');
    }
}
