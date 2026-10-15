<?php

namespace Tests\Feature;

use App\Jobs\GenerateAiAnalysis;
use App\Jobs\GenerateClinicalAnalysis;
use App\Models\AiAnalysis;
use App\Models\Appointment;
use App\Models\AppointmentReport;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\PatientProfile;
use App\Models\Referral;
use App\Models\TimeSlot;
use App\Models\User;
use App\Support\MockReferralDirectory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConsultationReferralTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.ai.key' => '']);
    }

    public function test_second_completed_visit_shows_only_this_doctors_history(): void
    {
        [$doctorUser, $doctor] = $this->makeDoctor('Dr Maya Chen');
        [$otherUser, $other] = $this->makeDoctor('Dr Leah Ortiz', 'Dermatology');
        [$stranger] = $this->makeDoctor('Dr Nia Cole', 'Neurology');
        $patient = $this->makePatient('Amina Rahman');

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

        $first = $this->appointment($patient, $doctor, [
            'appointment_date' => now()->addDays(2)->toDateString(),
            'appointment_time' => '09:00:00',
            'status' => 'completed',
            'reason' => 'Earlier cluster headache',
            'visit_type' => 'consultation',
        ]);

        Consultation::query()->create([
            'appointment_id' => $first->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'diagnosis' => 'Sinus history diagnosis',
            'plan' => 'Rest and fluids',
            'ai_status' => 'skipped',
        ]);

        AppointmentReport::query()->create([
            'appointment_id' => $first->id,
            'group_name' => 'Blood Test Reports',
            'path' => 'appointment-reports/old-labs.pdf',
            'original_name' => 'old-labs.pdf',
        ]);

        $hidden = $this->appointment($patient, $other, [
            'appointment_date' => now()->addDays(3)->toDateString(),
            'status' => 'completed',
            'reason' => 'Zephyr rash from clinic B',
        ]);

        AppointmentReport::query()->create([
            'appointment_id' => $hidden->id,
            'group_name' => 'X-Ray Scans',
            'path' => 'appointment-reports/hidden-zephyr.pdf',
            'original_name' => 'hidden-zephyr.pdf',
        ]);

        $second = $this->appointment($patient, $doctor, [
            'appointment_date' => now()->addDays(4)->toDateString(),
            'appointment_time' => '10:00:00',
            'status' => 'completed',
            'reason' => 'Current sinus check',
        ]);

        $this->actingAs($doctorUser)
            ->get(route('doctor.appointments.show', $second))
            ->assertOk()
            ->assertSee('Amina Rahman')
            ->assertSee('Current sinus check')
            ->assertSee('42')
            ->assertSee('O+')
            ->assertSee('128/82')
            ->assertSee('Not started')
            ->assertDontSee('AI analysis is not available for this booking yet.')
            ->assertDontSee('Earlier cluster headache')
            ->assertDontSee('old-labs.pdf')
            ->assertDontSee('Zephyr rash from clinic B')
            ->assertDontSee('hidden-zephyr.pdf');

        $this->actingAs($doctorUser)
            ->get(route('doctor.appointments.show', $first))
            ->assertOk()
            ->assertSee('Earlier cluster headache')
            ->assertSee('Sinus history diagnosis')
            ->assertSee('Rest and fluids')
            ->assertSee('Blood Test Reports')
            ->assertSee('old-labs.pdf')
            ->assertDontSee('Zephyr rash from clinic B')
            ->assertDontSee('hidden-zephyr.pdf');

        $this->actingAs($doctorUser)
            ->get(route('doctor.appointments', ['range' => 'all']))
            ->assertOk()
            ->assertSee('Amina Rahman')
            ->assertDontSee('Zephyr rash from clinic B');

        $this->actingAs($otherUser)
            ->get(route('doctor.appointments.show', $second))
            ->assertForbidden();

        $this->actingAs($stranger)
            ->get(route('doctor.patients.show', $patient))
            ->assertNotFound();

        $this->actingAs($doctorUser)
            ->get(route('doctor.patients.show', $patient))
            ->assertOk()
            ->assertSee('Amina Rahman')
            ->assertSee('Earlier cluster headache');
    }

    public function test_a_doctor_cannot_open_another_doctors_visit(): void
    {
        [$ownerUser, $owner] = $this->makeDoctor('Dr Maya Chen');
        [$otherUser] = $this->makeDoctor('Dr Leah Ortiz');
        $patient = $this->makePatient();
        $appointment = $this->appointment($patient, $owner);

        $this->actingAs($otherUser)
            ->get(route('doctor.appointments.show', $appointment))
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->put(route('doctor.appointments.consult.update', $appointment), [
                'clinical_notes' => 'Should not save',
                'diagnosis' => 'Nope',
                'plan' => 'Nope',
            ])
            ->assertForbidden();

        $this->actingAs($patient)
            ->get(route('doctor.appointments.show', $appointment))
            ->assertForbidden();

        $this->assertDatabaseCount('consultations', 0);
        $this->actingAs($ownerUser)
            ->get(route('doctor.appointments.show', $appointment))
            ->assertOk();
    }

    public function test_saving_a_consult_persists_the_note_without_starting_analysis(): void
    {
        [$doctorUser, $doctor] = $this->makeDoctor('Dr Maya Chen');
        $patient = $this->makePatient();
        $pending = $this->appointment($patient, $doctor, ['status' => 'pending']);

        $this->actingAs($doctorUser)
            ->put(route('doctor.appointments.consult.update', $pending), [
                'clinical_notes' => 'Too early',
                'diagnosis' => 'None',
                'plan' => 'Wait',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('consultations', 0);

        $appointment = $this->appointment($patient, $doctor, [
            'appointment_date' => now()->addDays(3)->toDateString(),
            'status' => 'confirmed',
        ]);

        Queue::fake();

        $this->actingAs($doctorUser)
            ->put(route('doctor.appointments.consult.update', $appointment), [
                'clinical_notes' => 'Patient reports a throbbing headache.',
                'diagnosis' => 'Tension headache',
                'plan' => 'Hydration and review.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Consult saved.');

        $this->assertSame('confirmed', $appointment->fresh()->status);
        $this->assertDatabaseHas('consultations', [
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'clinical_notes' => 'Patient reports a throbbing headache.',
            'diagnosis' => 'Tension headache',
            'plan' => 'Hydration and review.',
        ]);

        Queue::assertNotPushed(GenerateClinicalAnalysis::class);
        Queue::assertNotPushed(GenerateAiAnalysis::class);

        $followUpOn = now()->addDays(10)->toDateString();

        $this->actingAs($doctorUser)
            ->put(route('doctor.appointments.consult.update', $appointment), [
                'follow_up_instructions' => 'Check the reading again.',
                'follow_up_on' => $followUpOn,
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Consult saved.');

        $consultation = Consultation::query()->where('appointment_id', $appointment->id)->first();
        $this->assertNotNull($consultation);
        $this->assertSame('Patient reports a throbbing headache.', $consultation->clinical_notes);
        $this->assertSame('Tension headache', $consultation->diagnosis);
        $this->assertSame('Hydration and review.', $consultation->plan);
        $this->assertSame('Check the reading again.', $consultation->follow_up_instructions);
        $this->assertSame($followUpOn, $consultation->follow_up_on?->toDateString());

        $this->actingAs($doctorUser)
            ->put(route('doctor.appointments.consult.update', $appointment), [
                'clinical_notes' => 'Headache has eased.',
            ])
            ->assertRedirect();

        $consultation->refresh();
        $this->assertSame('Headache has eased.', $consultation->clinical_notes);
        $this->assertSame('Check the reading again.', $consultation->follow_up_instructions);
        $this->assertSame($followUpOn, $consultation->follow_up_on?->toDateString());
    }

    public function test_analysis_job_stores_a_ready_summary_and_referral_brief(): void
    {
        [, $doctor] = $this->makeDoctor('Dr Maya Chen');
        [, $other] = $this->makeDoctor('Dr Leah Ortiz');
        $patient = $this->makePatient('Amina Rahman', [
            'email' => 'amina.patient@example.com',
            'phone' => '555-0199',
        ]);

        PatientProfile::query()->create([
            'user_id' => $patient->id,
            'age' => 42,
            'blood_type' => 'O+',
            'health_notes' => [
                'bp' => ['known' => true, 'value' => '128/82'],
            ],
        ]);

        $prior = $this->appointment($patient, $doctor, [
            'appointment_date' => now()->addDays(1)->toDateString(),
            'status' => 'completed',
            'reason' => 'Earlier cluster headache',
        ]);

        Consultation::query()->create([
            'appointment_id' => $prior->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'diagnosis' => 'Prior diagnosis',
            'plan' => 'Prior plan',
            'ai_status' => 'skipped',
        ]);

        $this->appointment($patient, $other, [
            'status' => 'completed',
            'reason' => 'Zephyr rash from clinic B',
        ]);

        $appointment = $this->appointment($patient, $doctor, [
            'appointment_date' => now()->addDays(4)->toDateString(),
            'status' => 'confirmed',
            'reason' => 'Current sinus check',
            'stripe_session_id' => 'cs_secret_session',
        ]);

        AppointmentReport::query()->create([
            'appointment_id' => $appointment->id,
            'group_name' => 'Blood Test Reports',
            'path' => 'appointment-reports/'.$patient->id.'/secret-storage-path.pdf',
            'original_name' => 'cbc-results.pdf',
        ]);

        $consultation = Consultation::query()->create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'clinical_notes' => 'Typed migraine note',
            'diagnosis' => 'Tension headache',
            'plan' => 'Hydration and review.',
            'ai_status' => 'pending',
        ]);

        config([
            'services.ai.base_url' => 'https://generativelanguage.googleapis.com/v1beta',
            'services.ai.key' => 'test-key',
            'services.ai.model' => 'gemini-3.8-flash',
        ]);

        Http::preventStrayRequests();
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'patientInfo' => [
                                    'name' => 'Amina Rahman',
                                    'age' => '42',
                                    'gender' => 'not recorded',
                                    'bloodPressure' => '128/82',
                                    'diabetic' => 'not recorded',
                                    'hyperthyroidism' => 'not recorded',
                                ],
                                'overallSummary' => 'Short summary.',
                                'reportAnalyses' => [[
                                    'groupName' => 'Blood Test Reports',
                                    'analysis' => 'cbc-results.pdf was attached.',
                                    'warnings' => [],
                                ]],
                                'doctorRecommendations' => ['Review blood pressure.'],
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
        $this->assertSame('Short summary.', $consultation->ai_summary);
        $this->assertSame('Brief for the next doctor.', $consultation->ai_analysis['referral_brief']);
        $this->assertSame('Typed migraine note', $consultation->clinical_notes);
        $this->assertSame('Tension headache', $consultation->diagnosis);
        $this->assertSame('Hydration and review.', $consultation->plan);
        $this->assertNotNull($consultation->ai_generated_at);

        Http::assertSent(function ($request) use ($patient) {
            $body = json_encode($request->data());

            return str_contains($body, 'cbc-results.pdf')
                && str_contains($body, 'Earlier cluster headache')
                && str_contains($body, 'Typed migraine note')
                && str_contains((string) $request->url(), ':generateContent')
                && $request->hasHeader('x-goog-api-key', 'test-key')
                && ! str_contains((string) $request->url(), 'test-key')
                && ! str_contains($body, 'secret-storage-path')
                && ! str_contains($body, $patient->email)
                && ! str_contains($body, '555-0199')
                && ! str_contains($body, 'cs_secret_session')
                && ! str_contains($body, 'Zephyr rash from clinic B');
        });
    }

    public function test_analysis_job_marks_failed_and_keeps_the_typed_note(): void
    {
        [, $doctor] = $this->makeDoctor('Dr Maya Chen');
        $patient = $this->makePatient();
        $appointment = $this->appointment($patient, $doctor);

        $consultation = Consultation::query()->create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'clinical_notes' => 'Typed migraine note',
            'diagnosis' => 'Tension headache',
            'plan' => 'Hydration and review.',
            'ai_status' => 'pending',
        ]);

        config([
            'services.ai.base_url' => 'https://generativelanguage.googleapis.com/v1beta',
            'services.ai.key' => 'test-key',
            'services.ai.model' => 'gemini-3.8-flash',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response('unavailable', 500),
        ]);

        GenerateClinicalAnalysis::dispatchSync($consultation->id);

        $consultation->refresh();

        $this->assertSame('failed', $consultation->ai_status);
        $this->assertSame('Typed migraine note', $consultation->clinical_notes);
        $this->assertSame('Tension headache', $consultation->diagnosis);
        $this->assertSame('Hydration and review.', $consultation->plan);
    }

    public function test_analysis_is_skipped_when_the_api_key_is_empty(): void
    {
        [, $doctor] = $this->makeDoctor('Dr Maya Chen');
        $patient = $this->makePatient();
        $appointment = $this->appointment($patient, $doctor);

        $consultation = Consultation::query()->create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'clinical_notes' => 'Keep this note',
            'diagnosis' => 'Tension headache',
            'plan' => 'Hydration and review.',
            'ai_status' => 'pending',
            'ai_summary' => 'should be cleared',
        ]);

        config(['services.ai.key' => '']);
        Http::fake();

        GenerateClinicalAnalysis::dispatchSync($consultation->id);

        $consultation->refresh();

        $this->assertSame('skipped', $consultation->ai_status);
        $this->assertNull($consultation->ai_summary);
        $this->assertSame('Keep this note', $consultation->clinical_notes);
        Http::assertNothingSent();
    }

    public function test_owning_doctor_can_refer_and_the_recipient_can_read_the_snapshot(): void
    {
        Storage::fake('public');

        [$fromUser, $from] = $this->makeDoctor('Dr Maya Chen', 'Surgeon');
        [$toUser, $to] = $this->makeDoctor('Dr Noah Shah', 'Cardiology');
        [$thirdUser] = $this->makeDoctor('Dr Nia Cole', 'Neurology');
        $patient = $this->makePatient('Amina Rahman');

        PatientProfile::query()->create([
            'user_id' => $patient->id,
            'age' => 42,
            'blood_type' => 'O+',
            'health_notes' => [
                'bp' => ['known' => true, 'value' => '128/82'],
                'diabetic' => ['known' => false, 'value' => ''],
                'hyperthyroidism' => ['known' => false, 'value' => ''],
            ],
        ]);

        $appointment = $this->appointment($patient, $from, [
            'status' => 'confirmed',
            'reason' => 'Current sinus check',
        ]);

        $path = 'appointment-reports/'.$patient->id.'/2026-10-13/cbc.pdf';
        Storage::disk('public')->put($path, 'cbc-bytes');

        $report = AppointmentReport::query()->create([
            'appointment_id' => $appointment->id,
            'group_name' => 'Blood Test Reports',
            'path' => $path,
            'original_name' => 'cbc-results.pdf',
        ]);

        Consultation::query()->create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $from->id,
            'patient_id' => $patient->id,
            'clinical_notes' => 'Original typed plan for migraine.',
            'diagnosis' => 'Tension headache',
            'plan' => 'Hydration and review.',
            'ai_status' => 'ready',
            'ai_summary' => 'Summary frozen at send.',
            'ai_analysis' => [
                'history_relevant_to_this_doctor' => 'Earlier visit.',
                'report_inventory' => 'cbc-results.pdf was attached.',
                'points_for_the_doctor' => 'Review pressure.',
                'referral_brief' => 'Brief frozen at send.',
            ],
        ]);

        $this->actingAs($fromUser)
            ->post(route('doctor.referrals.store', $appointment), [
                'to_doctor_id' => $to->id,
                'reason' => 'Needs a cardiology opinion.',
            ])
            ->assertRedirect(route('doctor.appointments.show', $appointment))
            ->assertSessionHas('status', 'Referral sent to Dr Noah Shah.');

        $this->assertDatabaseCount('appointments', 1);
        $this->assertDatabaseCount('referrals', 1);
        $this->assertDatabaseCount('referral_reports', 1);

        $laterPath = 'appointment-reports/'.$patient->id.'/2026-10-13/later.pdf';
        Storage::disk('public')->put($laterPath, 'later-bytes');
        AppointmentReport::query()->create([
            'appointment_id' => $appointment->id,
            'group_name' => 'X-Ray Scans',
            'path' => $laterPath,
            'original_name' => 'later-scan.pdf',
        ]);

        $referral = Referral::query()->first();
        $consultation = $appointment->consultation;
        $consultation->update([
            'clinical_notes' => 'Edited sentence after sending.',
            'ai_summary' => 'Summary rewritten later.',
            'ai_analysis' => ['referral_brief' => 'Brief rewritten later.'],
        ]);
        $patient->patient_profile->update([
            'health_notes' => [
                'bp' => ['known' => true, 'value' => '90/60'],
            ],
        ]);

        $this->actingAs($toUser)
            ->get(route('doctor.referrals'))
            ->assertOk()
            ->assertSee('Amina Rahman')
            ->assertSee('Dr Maya Chen')
            ->assertSee('Sent');

        $this->actingAs($toUser)
            ->get(route('doctor.referrals.show', $referral))
            ->assertOk()
            ->assertSee('Original typed plan for migraine.')
            ->assertSee('Summary frozen at send.')
            ->assertSee('Brief frozen at send.')
            ->assertSee('cbc-results.pdf')
            ->assertSee('128/82')
            ->assertSee('Needs a cardiology opinion.')
            ->assertDontSee('Edited sentence after sending.')
            ->assertDontSee('Summary rewritten later.')
            ->assertDontSee('later-scan.pdf')
            ->assertDontSee('90/60');

        $this->actingAs($toUser)
            ->get(route('doctor.referrals.reports.show', [$referral, $report]))
            ->assertOk();

        $this->actingAs($fromUser)
            ->get(route('doctor.appointments.reports.show', [$appointment, $report]))
            ->assertOk();

        $this->actingAs($thirdUser)
            ->get(route('doctor.referrals.reports.show', [$referral, $report]))
            ->assertForbidden();

        $this->actingAs($thirdUser)
            ->get(route('doctor.referrals.show', $referral))
            ->assertForbidden();

        $this->actingAs($patient)
            ->get(route('patient.bookings'))
            ->assertOk()
            ->assertSee('Dr Maya Chen');
    }

    public function test_referral_sends_the_patient_summary_with_only_the_selected_reports(): void
    {
        [$fromUser, $from] = $this->makeDoctor('Dr Maya Chen', 'Surgeon');
        [$toUser, $to] = $this->makeDoctor('Dr Noah Shah', 'Cardiology');
        $patient = $this->makePatient('Amina Rahman', ['gender' => 'female']);

        PatientProfile::query()->create([
            'user_id' => $patient->id,
            'age' => 42,
            'blood_type' => 'O+',
        ]);

        $appointment = $this->appointment($patient, $from, [
            'status' => 'confirmed',
            'reason' => 'Current sinus check',
        ]);

        $kept = AppointmentReport::query()->create([
            'appointment_id' => $appointment->id,
            'group_name' => 'Blood Test Reports',
            'path' => 'appointment-reports/cbc.pdf',
            'original_name' => 'cbc-results.pdf',
        ]);
        $held = AppointmentReport::query()->create([
            'appointment_id' => $appointment->id,
            'group_name' => 'X-Ray Scans',
            'path' => 'appointment-reports/scan.pdf',
            'original_name' => 'later-scan.pdf',
        ]);

        AiAnalysis::query()->create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $from->id,
            'patient_id' => $patient->id,
            'initiated_by' => $fromUser->id,
            'status' => 'completed',
            'selected_report_ids' => [$kept->id, $held->id],
            'original_summary' => 'Sinus summary prepared for the next doctor.',
            'structured_output' => [
                'highlights' => ['Sinus pressure'],
                'key_findings' => [[
                    'title' => 'Hemoglobin',
                    'explanation' => 'Within the printed range.',
                    'observed_value' => '13.2',
                    'unit' => 'g/dL',
                    'reference_range' => '12-16',
                ]],
                'suggested_review_actions' => [[
                    'text' => 'Review the blood count with the patient.',
                ]],
                'limitations' => 'Only the selected blood report was read.',
                'report_interpretations' => [
                    [
                        'source_report_id' => $kept->id,
                        'source_label' => 'cbc-results.pdf',
                        'narrative' => 'Blood count is available for review.',
                        'rows' => [[
                            'parameter' => 'Hemoglobin',
                            'observed_value' => '13.2',
                            'unit' => 'g/dL',
                            'reference_range' => '12-16',
                            'flag' => 'normal',
                        ]],
                    ],
                    [
                        'source_report_id' => $held->id,
                        'source_label' => 'later-scan.pdf',
                        'narrative' => 'Scan reading that should stay off this referral.',
                        'rows' => [],
                    ],
                ],
            ],
            'completed_at' => now(),
        ]);

        $this->actingAs($fromUser)
            ->get(route('doctor.appointments.show', $appointment))
            ->assertOk()
            ->assertSee('Sinus summary prepared for the next doctor.')
            ->assertSee('This summary is sent with the reports you select below.');

        $this->actingAs($fromUser)
            ->post(route('doctor.referrals.store', $appointment), [
                'to_doctor_id' => $to->id,
                'reason' => 'Needs a cardiology opinion.',
                'report_ids_present' => '1',
                'report_ids' => [$kept->id],
            ])
            ->assertRedirect(route('doctor.appointments.show', $appointment));

        $analysis = AiAnalysis::query()->first();
        $analysis->update(['original_summary' => 'Summary rewritten after the referral.']);

        $referral = Referral::query()->first();
        $packet = $referral->summary_snapshot['patient_summary'];

        $this->assertSame('analysis', $packet['source']);
        $this->assertSame('Sinus summary prepared for the next doctor.', $packet['text']);
        $this->assertSame([$kept->id], $referral->summary_snapshot['report_ids']);
        $this->assertSame('Blood count is available for review.', $packet['report_notes'][0]['narrative']);
        $this->assertCount(1, $packet['report_notes']);

        $this->actingAs($toUser)
            ->get(route('doctor.referrals.show', $referral))
            ->assertOk()
            ->assertSee('Sinus summary prepared for the next doctor.')
            ->assertSee('Within the printed range.')
            ->assertSee('Blood count is available for review.')
            ->assertSee('cbc-results.pdf')
            ->assertSee('Female')
            ->assertDontSee('Scan reading that should stay off this referral.')
            ->assertDontSee('later-scan.pdf')
            ->assertDontSee('Summary rewritten after the referral.');
    }

    public function test_accept_and_decline_only_move_a_sent_referral_once(): void
    {
        [$fromUser, $from] = $this->makeDoctor('Dr Maya Chen');
        [$toUser, $to] = $this->makeDoctor('Dr Noah Shah', 'Cardiology');
        $patient = $this->makePatient();
        $appointment = $this->appointment($patient, $from, ['status' => 'completed']);

        $this->actingAs($fromUser)
            ->post(route('doctor.referrals.store', $appointment), [
                'to_doctor_id' => $to->id,
                'reason' => 'First opinion.',
            ])
            ->assertSessionHas('status', 'Referral sent to Dr Noah Shah.');

        $this->actingAs($fromUser)
            ->post(route('doctor.referrals.store', $appointment), [
                'to_doctor_id' => $to->id,
                'reason' => 'Second opinion.',
            ])
            ->assertSessionHas('status', 'Referral sent to Dr Noah Shah.');

        $first = Referral::query()->orderBy('id')->first();
        $second = Referral::query()->orderByDesc('id')->first();

        $this->actingAs($toUser)
            ->post(route('doctor.referrals.accept', $first))
            ->assertRedirect();

        $this->assertSame('accepted', $first->fresh()->status);
        $this->assertNotNull($first->fresh()->responded_at);

        $this->actingAs($toUser)
            ->post(route('doctor.referrals.accept', $first))
            ->assertStatus(422);

        $this->assertSame('accepted', $first->fresh()->status);

        $this->actingAs($fromUser)
            ->post(route('doctor.referrals.decline', $second))
            ->assertForbidden();

        $this->actingAs($toUser)
            ->post(route('doctor.referrals.decline', $second), [
                'decline_reason' => 'Schedule is full.',
            ])
            ->assertRedirect();

        $this->assertSame('declined', $second->fresh()->status);
        $this->assertSame('Schedule is full.', $second->fresh()->decline_reason);
        $this->assertNotNull($second->fresh()->responded_at);
        $this->assertSame('accepted', $first->fresh()->status);

        $this->actingAs($fromUser)
            ->get(route('doctor.referrals.show', $second))
            ->assertOk()
            ->assertSee('Schedule is full.')
            ->assertSee('declined this referral');
    }

    public function test_a_doctor_can_choose_another_doctor_and_that_doctor_can_book_the_visit(): void
    {
        [$fromUser, $from] = $this->makeDoctor('Dr Maya Chen', 'Surgeon');
        [$toUser, $to] = $this->makeDoctor('Dr Noah Shah', 'Cardiology');
        $this->makeDoctor('Dr Pending Cole', 'Neurology', 'pending');
        $patient = $this->makePatient('Amina Rahman');
        $appointment = $this->appointment($patient, $from, [
            'status' => 'confirmed',
            'reason' => 'Current sinus check',
        ]);
        $date = now()->addDays(2)->toDateString();

        TimeSlot::query()->create([
            'doctor_id' => $to->id,
            'day' => strtolower(Carbon::parse($date)->format('l')),
            'starting_time' => '09:00',
            'ending_time' => '12:00',
            'is_available' => true,
        ]);

        $this->actingAs($fromUser)
            ->get(route('doctor.appointments.show', $appointment))
            ->assertOk()
            ->assertSee('Refer to another doctor')
            ->assertSee('Dr Ananya Iyer')
            ->assertSee('Cardiology')
            ->assertDontSee('Dr Noah Shah')
            ->assertDontSee('name="to_doctor_id"', false)
            ->assertDontSee('Dr Pending Cole')
            ->assertDontSee('Sample specialists');

        $this->actingAs($patient)
            ->get(route('patient.bookings.show', $appointment))
            ->assertOk()
            ->assertDontSee('Refer to another doctor')
            ->assertDontSee('name="to_doctor_id"', false);

        $this->actingAs($fromUser)
            ->post(route('doctor.referrals.store', $appointment), [
                'to_doctor_id' => $to->id,
                'reason' => 'Needs a cardiology opinion.',
                'clinical_question' => 'Is this cardiac?',
                'urgency' => 'soon',
                'report_ids_present' => '1',
            ])
            ->assertRedirect(route('doctor.appointments.show', $appointment))
            ->assertSessionHas('status', 'Referral sent to Dr Noah Shah.');

        $referral = Referral::query()->firstOrFail();
        $this->assertSame('soon', $referral->urgency);
        $this->assertSame('Is this cardiac?', $referral->clinical_question);
        $this->assertSame(0, $referral->reports()->count());
        $this->assertTrue($toUser->notifications->contains(fn ($note) => ($note->data['title'] ?? null) === 'Referral received'));
        $this->assertTrue($patient->notifications->contains(fn ($note) => ($note->data['title'] ?? null) === 'You were referred'));

        $this->actingAs($fromUser)
            ->get(route('doctor.referrals.show', $referral))
            ->assertOk()
            ->assertSee('Awaiting response from Dr Noah Shah')
            ->assertSee('Is this cardiac?')
            ->assertDontSee('name="decline_reason"', false);

        $this->actingAs($toUser)
            ->post(route('doctor.referrals.accept', $referral))
            ->assertRedirect()
            ->assertSessionHas('status', 'Referral accepted.');

        $this->assertTrue($fromUser->fresh()->notifications->contains(
            fn ($note) => ($note->data['title'] ?? null) === 'Referral accepted'
        ));

        $this->actingAs($toUser)
            ->post(route('doctor.referrals.schedule', $referral), [
                'date' => $date,
                'time' => '09:00',
            ])
            ->assertSessionHas('status', 'Referral appointment booked.');

        $referral->refresh();
        $booked = Appointment::query()->where('source', 'referral')->first();

        $this->assertSame('appointment_booked', $referral->status);
        $this->assertNotNull($booked);
        $this->assertSame($to->id, (int) $booked->doctor_id);
        $this->assertSame($patient->id, (int) $booked->patient_id);
        $this->assertSame($referral->id, (int) $booked->referral_id);
        $this->assertSame((int) $booked->id, (int) $referral->proposed_appointment_id);
        $this->assertTrue($patient->fresh()->notifications->contains(
            fn ($note) => ($note->data['title'] ?? null) === 'Appointment confirmed'
        ));
        $this->assertTrue($fromUser->fresh()->notifications->contains(
            fn ($note) => ($note->data['title'] ?? null) === 'Referral visit booked'
        ));

        $this->actingAs($toUser)
            ->get(route('doctor.referrals.show', $referral))
            ->assertOk()
            ->assertSee('Open the booked visit')
            ->assertSee('Appointment booked');

        $this->actingAs($fromUser)
            ->get(route('doctor.patients.show', $patient))
            ->assertOk()
            ->assertSee('Dr Noah Shah')
            ->assertSee('Appointment booked');
    }

    public function test_referring_to_yourself_or_a_pending_doctor_fails_validation(): void
    {
        [$doctorUser, $doctor] = $this->makeDoctor('Dr Maya Chen');
        [, $pendingDoctor] = $this->makeDoctor('Dr Pending Cole', 'Cardiology', 'pending');
        $patient = $this->makePatient();
        $appointment = $this->appointment($patient, $doctor, ['status' => 'confirmed']);

        $this->actingAs($doctorUser)
            ->from(route('doctor.appointments.show', $appointment))
            ->post(route('doctor.referrals.store', $appointment), [
                'to_doctor_id' => $doctor->id,
                'reason' => 'Referring to myself.',
            ])
            ->assertRedirect(route('doctor.appointments.show', $appointment))
            ->assertSessionHasErrors('to_doctor_id');

        $this->assertDatabaseCount('referrals', 0);

        $this->actingAs($doctorUser)
            ->from(route('doctor.appointments.show', $appointment))
            ->post(route('doctor.referrals.store', $appointment), [
                'to_doctor_id' => $pendingDoctor->id,
                'reason' => 'Referring to a pending doctor.',
            ])
            ->assertRedirect(route('doctor.appointments.show', $appointment))
            ->assertSessionHasErrors('to_doctor_id');

        $this->assertDatabaseCount('referrals', 0);

        $held = $this->appointment($patient, $doctor, [
            'appointment_date' => now()->addDays(5)->toDateString(),
            'status' => 'pending',
        ]);

        [, $approved] = $this->makeDoctor('Dr Noah Shah', 'Cardiology');

        $this->actingAs($doctorUser)
            ->post(route('doctor.referrals.store', $held), [
                'to_doctor_id' => $approved->id,
                'reason' => 'Too early.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('referrals', 0);
    }

    public function test_a_visit_with_no_other_doctor_walks_through_sample_specialists(): void
    {
        [$doctorUser, $doctor] = $this->makeDoctor('Dr Maya Chen');
        $patient = $this->makePatient();
        $appointment = $this->appointment($patient, $doctor, [
            'status' => 'confirmed',
            'reason' => 'Chest pain and palpitations',
        ]);

        $this->actingAs($doctorUser)
            ->get(route('doctor.appointments.show', $appointment))
            ->assertOk()
            ->assertSee('Refer to another doctor')
            ->assertSee('Choose a specialist')
            ->assertSee('View profile')
            ->assertSee('Dr Ananya Iyer')
            ->assertSee('City Heart Clinic')
            ->assertSee('images/referral-doctors/ananya-iyer.jpg', false)
            ->assertSee('Qualifications')
            ->assertSee('Back to specialists')
            ->assertSee('Matches this visit')
            ->assertSee('Chest pain')
            ->assertSee('Psychiatry')
            ->assertSee('Ophthalmology')
            ->assertDontSee('name="to_doctor_id"', false)
            ->assertDontSee('No other approved doctors are available');

        $directory = collect(MockReferralDirectory::doctors());
        $this->assertCount(10, $directory);
        $this->assertCount(10, $directory->pluck('specialization')->unique());
    }

    public function test_a_sample_referral_is_listed_after_it_is_sent(): void
    {
        [$doctorUser, $doctor] = $this->makeDoctor('Dr Maya Chen');
        $patient = $this->makePatient('Amina Rahman');
        $appointment = $this->appointment($patient, $doctor, [
            'status' => 'confirmed',
            'reason' => 'Chest pain and palpitations',
        ]);
        $recipient = collect(MockReferralDirectory::doctors())->firstWhere('name', 'Dr Ananya Iyer');

        $this->actingAs($doctorUser)
            ->get(route('doctor.referrals'))
            ->assertOk()
            ->assertSee('Meera Joshi')
            ->assertSee('Dr Kabir Shah')
            ->assertSee('Rahul Menon');

        $response = $this->actingAs($doctorUser)
            ->postJson(route('doctor.referrals.preview.store', $appointment), [
                'to_doctor_id' => $recipient['id'],
                'reason' => 'Please assess this chest pain.',
                'clinical_question' => 'Is this angina?',
                'urgency' => 'soon',
            ])
            ->assertOk()
            ->assertJsonPath('referral.patient', 'Amina Rahman')
            ->assertJsonPath('referral.name', 'Dr Ananya Iyer')
            ->assertJsonPath('referral.status', 'Sent');

        $id = $response->json('referral.id');

        $this->actingAs($doctorUser)
            ->get(route('doctor.referrals'))
            ->assertOk()
            ->assertSee('Amina Rahman')
            ->assertSee('Dr Ananya Iyer')
            ->assertSee('Please assess this chest pain.')
            ->assertSee('Sent');

        $this->actingAs($doctorUser)
            ->get(route('doctor.referrals.preview', $id))
            ->assertOk()
            ->assertSee('Please assess this chest pain.')
            ->assertSee('Is this angina?')
            ->assertSee('AI-assisted patient summary')
            ->assertSee('for Chest pain and palpitations')
            ->assertSee('Reports')
            ->assertSee('No reports were attached to this referral.')
            ->assertSee('Awaiting response from Dr Ananya Iyer');
    }

    public function test_sample_referrals_keep_the_patient_details_and_summary_from_send_time(): void
    {
        [$doctorUser, $doctor] = $this->makeDoctor('Dr Maya Chen', 'Cardiology');
        $patient = $this->makePatient('Amina Rahman', ['gender' => 'female']);
        PatientProfile::query()->create([
            'user_id' => $patient->id,
            'age' => 42,
            'health_notes' => [
                'bp' => ['known' => true, 'value' => '128/82'],
                'diabetic' => ['known' => false, 'value' => ''],
                'hyperthyroidism' => ['known' => false, 'value' => ''],
            ],
        ]);
        $appointment = $this->appointment($patient, $doctor, [
            'status' => 'confirmed',
            'reason' => 'Chest pain and palpitations',
        ]);
        $recipient = collect(MockReferralDirectory::doctors())->firstWhere('name', 'Dr Ananya Iyer');

        $this->actingAs($doctorUser)
            ->get(route('doctor.referrals.preview', 'sample-in-meera'))
            ->assertOk()
            ->assertSee('Meera Joshi')
            ->assertSee('46 years')
            ->assertSee('Female')
            ->assertSee('Joint & Spine Care')
            ->assertSee('AI-assisted patient summary')
            ->assertSee('Reviewed and approved')
            ->assertSee('knee pain after a fall')
            ->assertSee('Blood Test Reports')
            ->assertSee('Hemoglobin (Hb)')
            ->assertSee('15.2 g/dL')
            ->assertSee('Current lab results for this episode are not available.');

        $this->actingAs($doctorUser)
            ->get(route('doctor.referrals.preview', 'sample-out-ananya'))
            ->assertOk()
            ->assertSee('Rahul Menon')
            ->assertSee('58 years')
            ->assertSee('Male')
            ->assertSee('Blood pressure 148/92')
            ->assertSee('City Heart Clinic')
            ->assertSee('Dr Ananya Iyer')
            ->assertSee('Awaiting response from Dr Ananya Iyer')
            ->assertSee('Hemoglobin (Hb)')
            ->assertSee('These results are from a prior visit.');

        $id = $this->actingAs($doctorUser)
            ->postJson(route('doctor.referrals.preview.store', $appointment), [
                'to_doctor_id' => $recipient['id'],
                'reason' => 'Please assess this chest pain.',
            ])
            ->assertOk()
            ->json('referral.id');

        $patient->patient_profile->update([
            'age' => 70,
            'health_notes' => [
                'bp' => ['known' => true, 'value' => '90/60'],
            ],
        ]);

        $this->actingAs($doctorUser)
            ->get(route('doctor.referrals.preview', $id))
            ->assertOk()
            ->assertSee('42 years')
            ->assertSee('Female')
            ->assertSee('128/82')
            ->assertSee('for Chest pain and palpitations')
            ->assertDontSee('70 years')
            ->assertDontSee('90/60');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{0: User, 1: Doctor}
     */
    private function makeDoctor(string $name, string $specialization = 'Surgeon', string $approval = 'approved'): array
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
            'approval_status' => $approval,
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
}
