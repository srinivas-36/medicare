<?php

namespace Tests\Feature;

use App\Jobs\GenerateAiAnalysis;
use App\Jobs\GenerateClinicalAnalysis;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\PatientProfile;
use App\Models\TimeSlot;
use App\Models\User;
use App\Services\StripeCheckout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AppointmentConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_upload_another_report_without_starting_analysis(): void
    {
        Storage::fake('public');
        Queue::fake();

        [$doctorUser, $doctor] = $this->makeDoctor();
        $patient = $this->makePatient();
        $stranger = $this->makePatient('Other Patient');
        $appointment = $this->appointment($patient, $doctor, [
            'reason' => 'Morning headache',
        ]);

        PatientProfile::query()->create([
            'user_id' => $patient->id,
            'age' => 42,
            'blood_type' => 'O+',
            'health_notes' => [
                'bp' => ['known' => true, 'value' => '128/82'],
            ],
        ]);

        $this->actingAs($stranger)
            ->post(route('patient.bookings.reports.store', $appointment), [
                'blood_reports' => [UploadedFile::fake()->create('secret.pdf', 20, 'application/pdf')],
            ])
            ->assertForbidden();

        $this->actingAs($patient)
            ->post(route('patient.bookings.reports.store', $appointment), [])
            ->assertSessionHasErrors('blood_reports');

        $this->actingAs($patient)
            ->post(route('patient.bookings.reports.store', $appointment), [
                'blood_reports' => [UploadedFile::fake()->create('follow-up-cbc.pdf', 40, 'application/pdf')],
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Reports added.');

        $this->assertDatabaseHas('appointment_reports', [
            'appointment_id' => $appointment->id,
            'group_name' => 'Blood Test Reports',
            'original_name' => 'follow-up-cbc.pdf',
        ]);

        Queue::assertNotPushed(GenerateClinicalAnalysis::class);
        Queue::assertNotPushed(GenerateAiAnalysis::class);

        $this->actingAs($patient)
            ->get(route('patient.bookings.show', $appointment))
            ->assertOk()
            ->assertDontSee('AI Analysis')
            ->assertDontSee('Generated assistance')
            ->assertSee('follow-up-cbc.pdf')
            ->assertSee('Add another report')
            ->assertSee('Remove')
            ->assertDontSee('128/82')
            ->assertDontSee('Morning headache')
            ->assertDontSee('555-0101')
            ->assertDontSee('Health notes');

        $this->actingAs($doctorUser)
            ->get(route('doctor.appointments.show', $appointment))
            ->assertOk()
            ->assertSee('AI Analysis')
            ->assertSee('follow-up-cbc.pdf')
            ->assertSee('Health notes')
            ->assertSee('128/82')
            ->assertSee('Morning headache')
            ->assertSee('555-0101')
            ->assertDontSee('Add another report');
    }

    public function test_patient_and_doctor_can_exchange_messages_on_the_visit(): void
    {
        [$doctorUser, $doctor] = $this->makeDoctor();
        [$otherDoctor] = $this->makeDoctor('Dr Leah Ortiz');
        $patient = $this->makePatient();
        $appointment = $this->appointment($patient, $doctor);

        $this->actingAs($patient)
            ->post(route('patient.bookings.messages.store', $appointment), [
                'body' => '   ',
            ])
            ->assertSessionHasErrors('body');

        $this->actingAs($patient)
            ->post(route('patient.bookings.messages.store', $appointment), [
                'body' => 'Can you look at the new blood file?',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Message sent.');

        $this->actingAs($doctorUser)
            ->post(route('doctor.appointments.messages.store', $appointment), [
                'body' => 'I will review it before the visit.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Message sent.');

        $this->actingAs($otherDoctor)
            ->post(route('doctor.appointments.messages.store', $appointment), [
                'body' => 'This should stay private.',
            ])
            ->assertForbidden();

        $this->actingAs($patient)
            ->get(route('patient.bookings.show', $appointment))
            ->assertOk()
            ->assertSee('Can you look at the new blood file?')
            ->assertSee('I will review it before the visit.')
            ->assertSee('Type a message')
            ->assertSee('Chat with')
            ->assertDontSee('Ask a question or share an update')
            ->assertDontSee('This should stay private.');

        $this->actingAs($doctorUser)
            ->get(route('doctor.appointments.show', $appointment))
            ->assertOk()
            ->assertSee('Can you look at the new blood file?')
            ->assertSee('I will review it before the visit.');

        $this->actingAs($otherDoctor)
            ->getJson(route('doctor.appointments.messages.index', $appointment))
            ->assertForbidden();

        $this->actingAs($patient)
            ->postJson(route('patient.bookings.messages.store', $appointment), [
                'body' => 'The file is the one from this morning.',
            ])
            ->assertOk()
            ->assertJsonPath('message.body', 'The file is the one from this morning.')
            ->assertJsonPath('message.mine', true);

        $this->actingAs($doctorUser)
            ->getJson(route('doctor.appointments.messages.index', $appointment))
            ->assertOk()
            ->assertJsonFragment(['body' => 'The file is the one from this morning.'])
            ->assertJsonFragment(['body' => 'I will review it before the visit.']);

        $this->assertNotNull(
            $appointment->messages()->where('body', 'The file is the one from this morning.')->value('read_at')
        );
    }

    public function test_a_confirmed_booking_does_not_start_analysis(): void
    {
        Queue::fake();

        [, $doctor, $date] = $this->bookableDoctor();
        $doctor->update(['ticket_price' => 0]);
        $patient = User::factory()->create([
            'role' => 'patient',
            'is_active' => true,
        ]);

        $this->mock(StripeCheckout::class, function ($mock) {
            $mock->shouldReceive('createSession')->never();
        });

        $this->actingAs($patient)
            ->post(route('doctors.book.store', $doctor), [
                'appointment_date' => $date,
                'slot_day' => strtolower(Carbon::parse($date)->format('l')),
                'starting_time' => '09:00',
                'ending_time' => '12:00',
                'health_issues' => 'Headache',
            ])
            ->assertRedirect();

        Queue::assertNotPushed(GenerateClinicalAnalysis::class);
        Queue::assertNotPushed(GenerateAiAnalysis::class);
        $this->assertDatabaseCount('consultations', 0);

        $paid = Appointment::query()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => Carbon::parse($date)->addDay()->toDateString(),
            'appointment_time' => '09:00:00',
            'ending_time' => '12:00:00',
            'status' => 'pending',
            'visit_type' => 'consultation',
            'payment_status' => 'unpaid',
            'ticket_price' => 150,
            'stripe_session_id' => 'cs_paid_analysis',
        ]);

        $this->bindStripeEvent((object) [
            'type' => 'checkout.session.completed',
            'data' => (object) [
                'object' => (object) [
                    'id' => 'cs_paid_analysis',
                    'client_reference_id' => (string) $paid->id,
                    'amount_total' => 15000,
                    'currency' => 'usd',
                    'payment_intent' => 'pi_analysis',
                ],
            ],
        ]);

        $this->postJson(route('stripe.webhook'), [])->assertOk();
        $this->postJson(route('stripe.webhook'), [])->assertOk();

        Queue::assertNotPushed(GenerateClinicalAnalysis::class);
        Queue::assertNotPushed(GenerateAiAnalysis::class);
        $this->assertSame('paid', $paid->fresh()->payment_status);
    }

    public function test_ready_analysis_is_shown_as_a_clinical_brief(): void
    {
        [, $doctor] = $this->makeDoctor();
        $patient = $this->makePatient();
        $appointment = $this->appointment($patient, $doctor);

        Consultation::query()->create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'ai_status' => 'ready',
            'ai_summary' => 'Short summary for the visit.',
            'ai_analysis' => [
                'history_relevant_to_this_doctor' => 'No earlier visits with this doctor.',
                'report_inventory' => 'Blood Test Reports: follow-up-cbc.pdf was attached.',
                'clinical_picture' => 'The visit reason and the attached file should be read together.',
                'points_for_the_doctor' => 'Confirm the headache pattern.',
                'clinical_recommendations' => [
                    'Ask when the headache started.',
                    'Review the attached file with the patient.',
                ],
                'referral_brief' => 'Internal brief for another doctor.',
                'report_reviews' => [[
                    'group' => 'Blood Test Reports',
                    'file' => 'follow-up-cbc.pdf',
                    'reading' => 'The file was attached. Its contents were not read.',
                    'attention' => 'Do not treat the filename as a result.',
                ]],
            ],
            'ai_generated_at' => now(),
        ]);

        $this->actingAs($doctor->user)
            ->get(route('doctor.appointments.show', $appointment))
            ->assertOk()
            ->assertSee('AI Analysis')
            ->assertSee('Earlier brief')
            ->assertDontSee('Short summary for the visit.');

        $this->actingAs($doctor->user)
            ->get(route('doctor.appointments.analysis', $appointment))
            ->assertOk()
            ->assertSee('Short summary for the visit.')
            ->assertSee('Clinical picture')
            ->assertSee('The visit reason and the attached file should be read together.')
            ->assertSee('Confirm the headache pattern.')
            ->assertSee('Ask when the headache started.')
            ->assertSee('Clinical recommendations')
            ->assertSee('The file was attached. Its contents were not read.')
            ->assertSee('Do not treat the filename as a result.')
            ->assertSee('Internal brief for another doctor.');

        $this->actingAs($patient)
            ->get(route('patient.bookings.show', $appointment))
            ->assertOk()
            ->assertDontSee('AI Analysis')
            ->assertDontSee('Short summary for the visit.')
            ->assertDontSee('Ask when the headache started.')
            ->assertDontSee('Internal brief for another doctor.');
    }

    public function test_patient_can_remove_an_uploaded_report(): void
    {
        Storage::fake('local');
        Queue::fake();

        [, $doctor] = $this->makeDoctor();
        $patient = $this->makePatient();
        $stranger = $this->makePatient('Other Patient');
        $appointment = $this->appointment($patient, $doctor);
        $path = 'appointment-reports/'.$patient->id.'/'.$appointment->appointment_date->toDateString().'/cbc.pdf';
        Storage::disk('local')->put($path, 'report');

        $report = $appointment->reports()->create([
            'group_name' => 'Blood Test Reports',
            'path' => $path,
            'original_name' => 'follow-up-cbc.pdf',
        ]);

        $this->actingAs($stranger)
            ->delete(route('patient.bookings.reports.destroy', [$appointment, $report]))
            ->assertForbidden();

        $this->assertDatabaseHas('appointment_reports', ['id' => $report->id]);

        $cancelled = $this->appointment($patient, $doctor, ['status' => 'cancelled']);
        $cancelledReport = $cancelled->reports()->create([
            'group_name' => 'Blood Test Reports',
            'path' => $path,
            'original_name' => 'cancelled.pdf',
        ]);

        $this->actingAs($patient)
            ->delete(route('patient.bookings.reports.destroy', [$cancelled, $cancelledReport]))
            ->assertForbidden();

        $this->actingAs($patient)
            ->delete(route('patient.bookings.reports.destroy', [$appointment, $report]))
            ->assertRedirect()
            ->assertSessionHas('status', 'Report removed.');

        $this->assertDatabaseMissing('appointment_reports', ['id' => $report->id]);
        Storage::disk('local')->assertMissing($path);
        Queue::assertNotPushed(GenerateClinicalAnalysis::class);
        Queue::assertNotPushed(GenerateAiAnalysis::class);

        $this->actingAs($patient)
            ->get(route('patient.bookings.show', $appointment))
            ->assertOk()
            ->assertDontSee('follow-up-cbc.pdf')
            ->assertDontSee('AI Analysis');
    }

    public function test_analysis_job_keeps_a_detailed_recommendation_list(): void
    {
        [, $doctor] = $this->makeDoctor();
        $patient = $this->makePatient();
        $appointment = $this->appointment($patient, $doctor);
        $consultation = Consultation::query()->create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'clinical_notes' => 'Keep this note',
            'ai_status' => 'pending',
        ]);

        config([
            'services.ai.base_url' => 'https://generativelanguage.googleapis.com/v1beta',
            'services.ai.key' => 'test-key',
            'services.ai.model' => 'gemini-3.8-flash',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'overallSummary' => 'Detailed summary.',
                                'patientInfo' => [
                                    'name' => 'Patient',
                                    'age' => 'not recorded',
                                    'gender' => 'not recorded',
                                    'bloodPressure' => 'not recorded',
                                    'diabetic' => 'not recorded',
                                    'hyperthyroidism' => 'not recorded',
                                ],
                                'reportAnalyses' => [],
                                'doctorRecommendations' => ['Confirm the timeline.', 'Repeat only if symptoms change.'],
                                'referralBrief' => 'Brief for the next doctor.',
                            ]),
                        ]],
                    ],
                ]],
            ]),
        ]);

        GenerateClinicalAnalysis::dispatchSync($consultation->id);

        $consultation->refresh();

        $this->assertSame('ready', $consultation->ai_status);
        $this->assertSame('Detailed summary.', $consultation->ai_summary);
        $this->assertSame('Brief for the next doctor.', $consultation->ai_analysis['referral_brief']);
        $this->assertSame(['Confirm the timeline.', 'Repeat only if symptoms change.'], $consultation->ai_analysis['clinical_recommendations']);
        $this->assertSame('Keep this note', $consultation->clinical_notes);
    }

    /**
     * @return array{0: User, 1: Doctor}
     */
    private function makeDoctor(string $name = 'Dr Maya Chen'): array
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
            'specialization' => 'Cardiology',
            'ticket_price' => 150,
            'approval_status' => 'approved',
        ]);

        return [$user, $doctor];
    }

    private function makePatient(string $name = 'Amina Rahman'): User
    {
        return User::factory()->create([
            'name' => $name,
            'phone' => '555-0101',
            'role' => 'patient',
            'gender' => 'female',
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
            'appointment_date' => now()->addDays(2)->toDateString(),
            'appointment_time' => '10:00:00',
            'ending_time' => '10:30:00',
            'status' => 'confirmed',
            'visit_type' => 'consultation',
            'payment_status' => 'paid',
            'reason' => 'Headache',
        ], $overrides));
    }

    /**
     * @return array{0: User, 1: Doctor, 2: string}
     */
    private function bookableDoctor(): array
    {
        $date = now()->addDay()->toDateString();
        [$user, $doctor] = $this->makeDoctor();

        TimeSlot::query()->create([
            'doctor_id' => $doctor->id,
            'day' => strtolower(Carbon::parse($date)->format('l')),
            'starting_time' => '09:00',
            'ending_time' => '12:00',
            'is_available' => true,
        ]);

        return [$user, $doctor, $date];
    }

    private ?object $stripeEventPayload = null;

    private function bindStripeEvent(object $event): void
    {
        $this->stripeEventPayload = $event;

        $this->mock(StripeCheckout::class, function ($mock) {
            $mock->shouldReceive('constructEvent')->andReturnUsing(function () {
                return $this->stripeEventPayload;
            });
        });
    }
}
