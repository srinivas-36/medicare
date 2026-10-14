<?php

namespace Tests\Feature;

use App\Jobs\GenerateAiAnalysis;
use App\Jobs\GenerateClinicalAnalysis;
use App\Models\AiAnalysis;
use App\Models\Appointment;
use App\Models\AppointmentReport;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\User;
use App\Services\AI\ClinicalOutputValidator;
use App\Services\AI\InvalidAnalysisOutput;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DoctorAnalysisWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploading_a_report_and_opening_the_visit_do_not_call_the_provider(): void
    {
        Storage::fake('local');
        Queue::fake();
        Http::preventStrayRequests();

        [$doctorUser, $doctor] = $this->makeDoctor();
        $patient = $this->makePatient();
        $appointment = $this->appointment($patient, $doctor);

        $this->actingAs($patient)
            ->post(route('patient.bookings.reports.store', $appointment), [
                'blood_reports' => [UploadedFile::fake()->create('cbc.txt', 20, 'text/plain')],
            ])
            ->assertRedirect();

        $this->actingAs($doctorUser)
            ->get(route('doctor.appointments.show', $appointment))
            ->assertOk();

        $this->actingAs($doctorUser)
            ->get(route('doctor.appointments.analysis', $appointment))
            ->assertOk()
            ->assertSee('Generate AI Analysis')
            ->assertSee('Not provided');

        Queue::assertNotPushed(GenerateClinicalAnalysis::class);
        Queue::assertNotPushed(GenerateAiAnalysis::class);
        Http::assertNothingSent();
        $this->assertDatabaseCount('ai_analyses', 0);
    }

    public function test_unauthorized_users_cannot_start_an_analysis(): void
    {
        [$doctorUser, $doctor] = $this->makeDoctor();
        [, $other] = $this->makeDoctor('Dr Leah Ortiz');
        $patient = $this->makePatient();
        $stranger = $this->makePatient('Other Patient');
        $appointment = $this->appointment($patient, $doctor);
        $foreign = $this->appointment($stranger, $other);

        $this->actingAs($patient)
            ->get(route('doctor.appointments.analysis', $appointment))
            ->assertForbidden();

        $this->actingAs($patient)
            ->post(route('doctor.appointments.analysis.store', $appointment))
            ->assertForbidden();

        $this->actingAs($doctorUser)
            ->get(route('doctor.appointments.analysis', $foreign))
            ->assertForbidden();

        $this->actingAs($doctorUser)
            ->post(route('doctor.appointments.analysis.store', $foreign))
            ->assertForbidden();

        $this->assertDatabaseCount('ai_analyses', 0);
    }

    public function test_doctor_can_analyze_only_selected_reports_and_keep_history(): void
    {
        Storage::fake('local');
        $this->configureAi();

        [$doctorUser, $doctor] = $this->makeDoctor();
        $patient = $this->makePatient('Amina Rahman', [
            'email' => 'amina.patient@example.com',
            'phone' => '555-0199',
        ]);
        $appointment = $this->appointment($patient, $doctor, [
            'reason' => 'Current sinus check',
            'stripe_session_id' => 'cs_secret_session',
        ]);

        Consultation::query()->create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'clinical_notes' => 'Typed migraine note',
            'diagnosis' => 'Tension headache',
            'plan' => 'Hydration and review.',
        ]);

        $selected = $this->report($appointment, 'cbc-results.txt', 'Hemoglobin 14.2 g/dL');
        $hidden = $this->report($appointment, 'hidden-scan.txt', 'Do not send this scan', 'X-Ray Scans');

        Http::preventStrayRequests();
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::sequence()
                ->push($this->providerBody('First summary.'))
                ->push($this->providerBody('Second summary.')),
        ]);

        $this->actingAs($doctorUser)
            ->post(route('doctor.appointments.analysis.store', $appointment), [
                'report_ids' => [$selected->id, $hidden->id + 1000],
            ])
            ->assertSessionHasErrors('report_ids');

        $this->assertDatabaseCount('ai_analyses', 0);
        Http::assertNothingSent();

        $this->actingAs($doctorUser)
            ->post(route('doctor.appointments.analysis.store', $appointment), [
                'report_ids' => [$selected->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Analysis ready for review.');

        $first = AiAnalysis::query()->first();
        $this->assertSame('completed', $first->status);
        $this->assertSame('First summary.', $first->original_summary);
        $this->assertSame([$selected->id], $first->selected_report_ids);
        $this->assertSame('Tension headache', $appointment->consultation->fresh()->diagnosis);
        $this->assertSame('Typed migraine note', $appointment->consultation->fresh()->clinical_notes);
        $this->assertDatabaseHas('ai_analysis_audits', [
            'ai_analysis_id' => $first->id,
            'action' => 'completed',
        ]);

        Http::assertSent(function ($request) use ($patient) {
            $body = json_encode($request->data());

            return str_contains($body, 'cbc-results.txt')
                && str_contains($body, 'Current sinus check')
                && str_contains($body, base64_encode('Hemoglobin 14.2 g/dL'))
                && ! str_contains($body, 'hidden-scan.txt')
                && ! str_contains($body, base64_encode('Do not send this scan'))
                && ! str_contains($body, $patient->email)
                && ! str_contains($body, '555-0199')
                && ! str_contains($body, 'cs_secret_session')
                && ! str_contains((string) $request->url(), 'test-key');
        });

        $this->actingAs($doctorUser)
            ->post(route('doctor.appointments.analysis.store', $appointment), [
                'report_ids' => [$selected->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('ai_analyses', 2);
        $this->assertSame('First summary.', $first->fresh()->original_summary);

        $this->actingAs($doctorUser)
            ->get(route('doctor.appointments.analysis', [$appointment, $first]))
            ->assertOk()
            ->assertSee('First summary.')
            ->assertSee('Analysis history')
            ->assertDontSee('Second summary.');

        $this->actingAs($doctorUser)
            ->get(route('doctor.appointments.analysis', $appointment))
            ->assertOk()
            ->assertSee('Second summary.')
            ->assertSee('Hemoglobin')
            ->assertSee('Decision support only');
    }

    public function test_a_second_submit_does_not_start_another_job_while_one_is_open(): void
    {
        Http::preventStrayRequests();

        [$doctorUser, $doctor] = $this->makeDoctor();
        $patient = $this->makePatient();
        $appointment = $this->appointment($patient, $doctor);

        AiAnalysis::query()->create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'initiated_by' => $doctorUser->id,
            'status' => 'generating',
            'progress' => 'generating',
            'selected_report_ids' => [],
            'started_at' => now(),
        ]);

        $this->actingAs($doctorUser)
            ->post(route('doctor.appointments.analysis.store', $appointment))
            ->assertRedirect()
            ->assertSessionHas('status', 'An analysis is already running for this visit.');

        $this->assertDatabaseCount('ai_analyses', 1);
        Http::assertNothingSent();
    }

    public function test_provider_and_malformed_failures_are_recorded_safely(): void
    {
        Storage::fake('local');
        $this->configureAi();

        [$doctorUser, $doctor] = $this->makeDoctor();
        $patient = $this->makePatient();
        $appointment = $this->appointment($patient, $doctor);
        Consultation::query()->create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'clinical_notes' => 'Keep this note',
            'diagnosis' => 'Tension headache',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::sequence()
                ->push('SECRET_PROVIDER_BODY', 500)
                ->push([
                    'candidates' => [[
                        'content' => [
                            'parts' => [[
                                'text' => 'SECRET_MALFORMED_TOKEN',
                            ]],
                        ],
                    ]],
                ]),
        ]);

        $this->actingAs($doctorUser)
            ->post(route('doctor.appointments.analysis.store', $appointment))
            ->assertRedirect();

        $failed = AiAnalysis::query()->first();
        $this->assertSame('failed', $failed->status);
        $this->assertSame('provider_error', $failed->error_code);

        $this->actingAs($doctorUser)
            ->get(route('doctor.appointments.analysis', [$appointment, $failed]))
            ->assertOk()
            ->assertSee('Amina Rahman')
            ->assertSee('Headache')
            ->assertSee('Tension headache')
            ->assertSee('Clinical overview')
            ->assertSee('Key findings')
            ->assertSee('Next steps')
            ->assertDontSee('Could not finish')
            ->assertDontSee('Findings from the chart')
            ->assertDontSee('SECRET_PROVIDER_BODY');

        $this->actingAs($doctorUser)
            ->post(route('doctor.appointments.analysis.store', $appointment))
            ->assertRedirect();

        $invalid = AiAnalysis::query()->orderByDesc('id')->first();
        $this->assertSame('failed', $invalid->status);
        $this->assertSame('invalid_output', $invalid->error_code);
        $this->assertSame('Keep this note', $appointment->consultation->fresh()->clinical_notes);

        $this->actingAs($doctorUser)
            ->get(route('doctor.appointments.analysis', [$appointment, $invalid]))
            ->assertOk()
            ->assertSee('Amina Rahman')
            ->assertDontSee('SECRET_MALFORMED_TOKEN');
    }

    public function test_doctor_review_and_approval_are_audited_and_do_not_change_the_visit_note(): void
    {
        Storage::fake('local');
        $this->configureAi();

        [$doctorUser, $doctor] = $this->makeDoctor();
        [$recipientUser, $recipient] = $this->makeDoctor('Dr Noah Shah', 'Cardiology');
        $patient = $this->makePatient();
        $appointment = $this->appointment($patient, $doctor, ['status' => 'confirmed']);

        Consultation::query()->create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'clinical_notes' => 'Typed migraine note',
            'diagnosis' => 'Tension headache',
            'plan' => 'Hydration and review.',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response($this->providerBody('Unapproved model summary.')),
        ]);

        $this->actingAs($doctorUser)
            ->post(route('doctor.appointments.analysis.store', $appointment))
            ->assertRedirect();

        $analysis = AiAnalysis::query()->first();

        $this->actingAs($doctorUser)
            ->post(route('doctor.referrals.store', $appointment), [
                'to_doctor_id' => $recipient->id,
                'reason' => 'Needs a cardiology opinion.',
            ])
            ->assertRedirect();

        $this->actingAs($recipientUser)
            ->get(route('doctor.referrals.show', $appointment->referrals()->first()))
            ->assertOk()
            ->assertSee('Typed migraine note')
            ->assertDontSee('Unapproved model summary.');

        $this->actingAs($doctorUser)
            ->put(route('doctor.appointments.analysis.review', [$appointment, $analysis]), [
                'clinician_summary' => 'Clinician checked the timeline.',
                'doctor_notes' => 'Reviewed with the chart.',
                'reviewed' => [0],
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Review saved. The original analysis was left unchanged.');

        $analysis->refresh();
        $this->assertSame('Unapproved model summary.', $analysis->original_summary);
        $this->assertSame('Clinician checked the timeline.', $analysis->clinician_summary);
        $this->assertSame([0], $analysis->reviewed_findings);

        $this->actingAs($doctorUser)
            ->post(route('doctor.appointments.analysis.approve', [$appointment, $analysis]))
            ->assertRedirect();

        $analysis->refresh();
        $this->assertNotNull($analysis->approved_at);
        $this->assertSame('Clinician checked the timeline.', $analysis->approved_summary);
        $this->assertSame('Tension headache', $appointment->consultation->fresh()->diagnosis);
        $this->assertSame('Typed migraine note', $appointment->consultation->fresh()->clinical_notes);
        $this->assertDatabaseHas('ai_analysis_audits', [
            'ai_analysis_id' => $analysis->id,
            'user_id' => $doctorUser->id,
            'action' => 'approved',
        ]);

        $this->actingAs($doctorUser)
            ->post(route('doctor.referrals.store', $appointment), [
                'to_doctor_id' => $recipient->id,
                'reason' => 'Approved summary can travel with the referral.',
            ])
            ->assertRedirect();

        $approvedReferral = $appointment->referrals()->orderByDesc('id')->first();

        $this->actingAs($recipientUser)
            ->get(route('doctor.referrals.show', $approvedReferral))
            ->assertOk()
            ->assertSee('Clinician checked the timeline.')
            ->assertDontSee('Unapproved model summary.');
    }

    public function test_malformed_output_is_rejected_by_the_validator(): void
    {
        $validator = new ClinicalOutputValidator;

        $this->expectException(InvalidAnalysisOutput::class);

        $validator->validate(['clinical_summary' => ''], []);
    }

    /**
     * @return array<string, mixed>
     */
    private function providerBody(string $summary): array
    {
        return [
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => json_encode([
                            'clinical_summary' => $summary,
                            'patient_context' => [
                                'reason' => 'Current sinus check',
                                'background' => 'Earlier headache visit.',
                                'symptoms' => 'Sinus check',
                            ],
                            'key_findings' => [[
                                'title' => 'Hemoglobin',
                                'category' => 'extracted_measurement',
                                'priority' => 'routine',
                                'observed_value' => '14.2',
                                'unit' => 'g/dL',
                                'reference_range' => '13-17',
                                'explanation' => 'Printed inside the supplied range.',
                                'uncertainty' => 'The file was read as text.',
                                'page' => '1',
                                'file_index' => 1,
                            ]],
                            'report_interpretations' => [[
                                'file_index' => 1,
                                'document_type' => 'Blood Test Reports',
                                'report_date' => '2026-10-01',
                                'specimen' => 'Blood',
                                'narrative' => 'The attached file lists hemoglobin.',
                                'extraction_note' => '',
                                'rows' => [[
                                    'parameter' => 'Hemoglobin',
                                    'observed_value' => '14.2',
                                    'unit' => 'g/dL',
                                    'reference_range' => '13-17',
                                    'flag' => 'normal',
                                ]],
                            ]],
                            'differential_considerations' => [],
                            'missing_information' => ['Allergy list is not recorded.'],
                            'suggested_review_actions' => [[
                                'kind' => 'verify',
                                'text' => 'Confirm the timeline with the patient.',
                            ]],
                            'source_references' => [[
                                'kind' => 'report',
                                'label' => 'cbc-results.txt',
                                'file_index' => 1,
                            ]],
                            'limitations' => 'Duration was not recorded.',
                        ]),
                    ]],
                ],
            ]],
        ];
    }

    private function configureAi(): void
    {
        config([
            'services.ai.base_url' => 'https://generativelanguage.googleapis.com/v1beta',
            'services.ai.key' => 'test-key',
            'services.ai.model' => 'gemini-test',
            'services.ai.fallback_models' => [],
        ]);
    }

    /**
     * @return array{0: User, 1: Doctor}
     */
    private function makeDoctor(string $name = 'Dr Maya Chen', string $specialization = 'Surgeon'): array
    {
        $user = User::factory()->create([
            'name' => $name,
            'role' => 'doctor',
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makePatient(string $name = 'Amina Rahman', array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'name' => $name,
            'role' => 'patient',
            'is_active' => true,
        ], $overrides));
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
            'status' => 'confirmed',
            'visit_type' => 'consultation',
            'reason' => 'Headache',
        ], $overrides));
    }

    private function report(Appointment $appointment, string $name, string $contents, string $group = 'Blood Test Reports'): AppointmentReport
    {
        $path = 'appointment-reports/'.$appointment->patient_id.'/'.$name;
        Storage::disk('local')->put($path, $contents);

        return $appointment->reports()->create([
            'group_name' => $group,
            'path' => $path,
            'original_name' => $name,
        ]);
    }
}
