<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\PatientProfile;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AppointmentPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_and_patient_lists_filter_page_and_keep_the_query_on_the_detail_page(): void
    {
        [$doctorUser, $doctor] = $this->makeDoctor('Dr Maya Chen', 'Cardiology');
        [$otherUser, $other] = $this->makeDoctor('Dr Leah Ortiz', 'Dermatology');
        $patient = $this->makePatient('Amina Rahman', 'amina@example.com', '555-0101');
        $otherPatient = $this->makePatient('Zara Khan', 'zara@example.com', '555-0199');

        PatientProfile::query()->create([
            'user_id' => $patient->id,
            'age' => 42,
            'blood_type' => 'O+',
            'health_notes' => [
                'bp' => ['known' => true, 'value' => '128/82'],
                'diabetic' => ['known' => true, 'value' => 'no'],
                'hyperthyroidism' => ['known' => false, 'value' => ''],
            ],
        ]);

        TimeSlot::query()->create([
            'doctor_id' => $doctor->id,
            'day' => 'monday',
            'starting_time' => '09:00',
            'ending_time' => '12:00',
            'is_available' => true,
        ]);

        $match = $this->appointment($patient, $doctor, [
            'appointment_date' => '2026-10-12',
            'appointment_time' => '09:00:00',
            'ending_time' => '12:00:00',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'ticket_price' => 150,
            'reason' => 'Morning headache',
        ]);

        $this->appointment($patient, $doctor, [
            'appointment_date' => '2026-10-13',
            'appointment_time' => '14:00:00',
            'ending_time' => '15:00:00',
            'status' => 'completed',
            'payment_status' => 'paid',
            'reason' => 'Afternoon review',
        ]);

        $hidden = $this->appointment($otherPatient, $other, [
            'appointment_date' => '2026-10-12',
            'appointment_time' => '09:00:00',
            'ending_time' => '12:00:00',
            'reason' => 'Hidden clinic note',
        ]);

        $filters = [
            'date' => '2026-10-12',
            'startingTime' => '09:00',
            'endingTime' => '12:00',
            'status' => 'pending',
            'q' => 'Amina',
        ];

        $this->actingAs($doctorUser)
            ->get(route('doctor.appointments', $filters))
            ->assertOk()
            ->assertSee('Your appointments')
            ->assertSee('1 appointment')
            ->assertSee('Amina Rahman')
            ->assertSee('12 Oct 2026')
            ->assertSee('Mon, 9:00 AM')
            ->assertSee('Pending')
            ->assertSee('Unpaid')
            ->assertDontSee('Zara Khan')
            ->assertDontSee('Afternoon review')
            ->assertDontSee('Hidden clinic note')
            ->assertSee('date=2026-10-12', false)
            ->assertSee('startingTime=09%3A00', false);

        $this->actingAs($doctorUser)
            ->get(route('doctor.appointments', ['date' => '2026-11-01']))
            ->assertOk()
            ->assertSee('No appointments match these filters.')
            ->assertDontSee('You do not have any appointments yet.');

        $this->actingAs($doctorUser)
            ->get(route('doctor.appointments.show', array_merge(['appointment' => $match], $filters)))
            ->assertOk()
            ->assertSee('Back to appointments')
            ->assertSee('Amina Rahman')
            ->assertSee('Cardiology')
            ->assertSee('Monday')
            ->assertSee('150.00 USD')
            ->assertSee('amina@example.com')
            ->assertSee('555-0101')
            ->assertSee('42')
            ->assertSee('O+')
            ->assertSee('128/82')
            ->assertSee('No')
            ->assertSee('Not known')
            ->assertSee('Morning headache')
            ->assertSee('No reports were attached to this booking.')
            ->assertSee('No notes from the doctor yet.')
            ->assertSee('AI analysis is not available for this booking yet.')
            ->assertSee('date=2026-10-12', false)
            ->assertSee('startingTime=09%3A00', false)
            ->assertSee('endingTime=12%3A00', false)
            ->assertSee('status=pending', false)
            ->assertDontSee('Hidden clinic note')
            ->assertDontSee('zara@example.com');

        $this->actingAs($otherUser)
            ->get(route('doctor.appointments.show', $match))
            ->assertForbidden();

        $this->actingAs($patient)
            ->get(route('doctor.appointments.show', $match))
            ->assertForbidden();

        $this->actingAs($patient)
            ->get(route('patient.bookings', [
                'date' => '2026-10-12',
                'startingTime' => '09:00',
                'endingTime' => '12:00',
                'q' => 'Maya',
            ]))
            ->assertOk()
            ->assertSee('Your bookings')
            ->assertSee('Dr Maya Chen')
            ->assertSee('Cardiology')
            ->assertSee('1 appointment')
            ->assertDontSee('Dr Leah Ortiz')
            ->assertDontSee('128/82');

        $this->actingAs($patient)
            ->get(route('patient.bookings.show', array_merge(['appointment' => $match], $filters)))
            ->assertOk()
            ->assertSee('Back to bookings')
            ->assertSee('Dr Maya Chen')
            ->assertSee($doctorUser->email)
            ->assertDontSee('128/82')
            ->assertDontSee('Morning headache')
            ->assertDontSee('555-0101')
            ->assertSee('date=2026-10-12', false);

        $this->actingAs($otherPatient)
            ->get(route('patient.bookings.show', $match))
            ->assertForbidden();

        $this->actingAs($patient)
            ->get(route('patient.bookings.show', $hidden))
            ->assertForbidden();
    }

    public function test_lists_page_ten_rows_and_an_empty_account_has_its_own_message(): void
    {
        [$doctorUser, $doctor] = $this->makeDoctor('Dr Maya Chen');

        for ($number = 1; $number <= 11; $number++) {
            $this->appointment($this->makePatient(sprintf('Listed %02d', $number)), $doctor, [
                'appointment_date' => sprintf('2026-10-%02d', $number),
                'appointment_time' => '09:00:00',
                'ending_time' => '09:30:00',
            ]);
        }

        $this->actingAs($doctorUser)
            ->get(route('doctor.appointments'))
            ->assertOk()
            ->assertSee('11 appointments')
            ->assertSee('Listed 11')
            ->assertSee('Previous')
            ->assertSee('Next')
            ->assertDontSee('Listed 01');

        $this->actingAs($doctorUser)
            ->get(route('doctor.appointments', ['page' => 2]))
            ->assertOk()
            ->assertSee('Listed 01')
            ->assertDontSee('Listed 11');

        $emptyDoctor = $this->makeDoctor('Dr Nia Cole')[0];

        $this->actingAs($emptyDoctor)
            ->get(route('doctor.appointments'))
            ->assertOk()
            ->assertSee('You do not have any appointments yet.')
            ->assertDontSee('Previous');
    }

    public function test_detail_renders_saved_notes_and_gemini_shaped_analysis(): void
    {
        [$doctorUser, $doctor] = $this->makeDoctor('Dr Maya Chen', 'Cardiology');
        $patient = $this->makePatient('Amina Rahman');
        $appointment = $this->appointment($patient, $doctor, [
            'appointment_date' => '2026-10-12',
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);

        Consultation::query()->create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'clinical_notes' => 'Throbbing headache since Monday.',
            'ai_status' => 'ready',
            'ai_summary' => 'This older summary should stay out of the card.',
            'ai_analysis' => [
                'patientInfo' => [
                    'name' => 'Amina Rahman',
                    'age' => '42',
                    'gender' => 'Female',
                    'bloodPressure' => '128/82',
                    'diabetic' => 'No',
                    'hyperthyroidism' => 'Not known',
                ],
                'overallSummary' => 'Reports suggest a tension pattern.',
                'reportAnalyses' => [[
                    'groupName' => 'Blood Test Reports',
                    'reportUrls' => ['https://reports.example/cbc.pdf'],
                    'analysis' => 'The attached file name is cbc.pdf.',
                    'warnings' => ['File contents were not read.'],
                ]],
                'doctorRecommendations' => ['Review the blood pressure.', 'Repeat the visit in two weeks.'],
            ],
        ]);

        $this->actingAs($doctorUser)
            ->get(route('doctor.appointments.show', $appointment))
            ->assertOk()
            ->assertSee('Throbbing headache since Monday.')
            ->assertSee('Paid')
            ->assertSee('Patient overview')
            ->assertSee('Reports suggest a tension pattern.')
            ->assertSee('Blood Test Reports')
            ->assertSee('https://reports.example/cbc.pdf')
            ->assertSee('File contents were not read.')
            ->assertSee('The attached file name is cbc.pdf.')
            ->assertSee('Review the blood pressure.')
            ->assertSee('Repeat the visit in two weeks.')
            ->assertDontSee('This older summary should stay out of the card.');

        $this->actingAs($patient)
            ->get(route('patient.bookings.show', $appointment))
            ->assertOk()
            ->assertSee('Throbbing headache since Monday.')
            ->assertSee('Reports suggest a tension pattern.')
            ->assertSee('Review the blood pressure.')
            ->assertDontSee('Health notes');
    }

    public function test_clearing_filters_returns_the_unfiltered_list(): void
    {
        [$doctorUser, $doctor] = $this->makeDoctor('Dr Maya Chen');
        $patient = $this->makePatient('Amina Rahman');
        $this->appointment($patient, $doctor);

        $this->actingAs($doctorUser)
            ->get(route('doctor.appointments', ['status' => 'not-a-status', 'date' => '']))
            ->assertRedirect(route('doctor.appointments'));

        $this->actingAs($doctorUser)
            ->get(route('doctor.appointments', ['slot' => '09:00|12:00', 'q' => 'Amina']))
            ->assertRedirect(route('doctor.appointments', [
                'startingTime' => '09:00',
                'endingTime' => '12:00',
                'q' => 'Amina',
            ]));
    }

    /**
     * @return array{0: User, 1: Doctor}
     */
    private function makeDoctor(string $name, string $specialization = 'Surgeon'): array
    {
        $user = User::factory()->create([
            'name' => $name,
            'role' => 'doctor',
            'phone' => '555-0142',
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'is_active' => true,
        ]);

        $doctor = Doctor::query()->create([
            'user_id' => $user->id,
            'specialization' => $specialization,
            'ticket_price' => 150,
            'approval_status' => 'approved',
        ]);

        return [$user, $doctor];
    }

    private function makePatient(string $name, string $email = '', string $phone = '555-0100'): User
    {
        return User::factory()->create([
            'name' => $name,
            'email' => $email !== '' ? $email : fake()->unique()->safeEmail(),
            'phone' => $phone,
            'gender' => 'female',
            'role' => 'patient',
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function appointment(User $patient, Doctor $doctor, array $overrides = []): Appointment
    {
        return Appointment::query()->create(array_merge([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => '2026-10-12',
            'appointment_time' => '10:00:00',
            'ending_time' => '10:30:00',
            'status' => 'confirmed',
            'visit_type' => 'consultation',
            'payment_status' => 'unpaid',
            'ticket_price' => 150,
            'reason' => 'Headache',
        ], $overrides));
    }
}
