<?php

namespace Tests\Feature;

use App\Jobs\GenerateClinicalAnalysis;
use App\Models\Appointment;
use App\Models\AppointmentReport;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\PatientProfile;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('AI analysis is not available for this booking yet.')
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

    public function test_saving_a_consult_on_a_confirmed_visit_persists_the_note_and_queues_the_job(): void
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

        Queue::assertPushed(GenerateClinicalAnalysis::class, function (GenerateClinicalAnalysis $job) {
            return $job->consultationId === Consultation::query()->value('id');
        });
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
            'services.ai.base_url' => 'https://api.openai.com/v1',
            'services.ai.key' => 'test-key',
            'services.ai.model' => 'gpt-4o-mini',
        ]);

        Http::preventStrayRequests();
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'summary' => 'Short summary.',
                            'history_relevant_to_this_doctor' => 'Earlier headache visit.',
                            'report_inventory' => 'cbc-results.pdf was attached.',
                            'points_for_the_doctor' => 'Review blood pressure.',
                            'referral_brief' => 'Brief for the next doctor.',
                        ]),
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
                && str_contains((string) $request->url(), '/chat/completions')
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
            'services.ai.base_url' => 'https://api.openai.com/v1',
            'services.ai.key' => 'test-key',
            'services.ai.model' => 'gpt-4o-mini',
        ]);

        Http::fake([
            'https://api.openai.com/v1/*' => Http::response('unavailable', 500),
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
            ->assertSessionHas('status', 'Referral sent.');

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
            ->assertSessionHas('status', 'Referral sent.');

        $this->actingAs($fromUser)
            ->post(route('doctor.referrals.store', $appointment), [
                'to_doctor_id' => $to->id,
                'reason' => 'Second opinion.',
            ])
            ->assertSessionHas('status', 'Referral sent.');

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
            ->post(route('doctor.referrals.decline', $second))
            ->assertRedirect();

        $this->assertSame('declined', $second->fresh()->status);
        $this->assertNotNull($second->fresh()->responded_at);
        $this->assertSame('accepted', $first->fresh()->status);
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
