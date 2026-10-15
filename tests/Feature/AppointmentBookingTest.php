<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorReview;
use App\Models\TimeSlot;
use App\Models\User;
use App\Services\StripeCheckout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AppointmentBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_books_an_open_slot_and_can_cancel_it(): void
    {
        [$doctorUser, $doctor, $date] = $this->makeDoctor();
        $patient = $this->makePatient();

        $this->get(route('doctors.show', $doctorUser->slug))
            ->assertOk()
            ->assertSee('150.00 USD')
            ->assertSee('Dr Maya Chen');

        $this->mockCheckout();

        $this->actingAs($patient)
            ->get(route('doctors.book', $doctor))
            ->assertOk()
            ->assertSee('Book your appointment')
            ->assertSee('150.00 USD')
            ->assertSee('Available time slots')
            ->assertSee('Blood tests')
            ->assertSee('"start":"09:00","end":"12:00"', false)
            ->assertDontSee('"start":"09:30"', false)
            ->assertDontSee('name="visit_type"', false);

        $this->actingAs($patient)
            ->post(route('doctors.book.store', $doctor), $this->window($date))
            ->assertRedirect();

        $appointment = Appointment::query()->first();

        $this->assertNotNull($appointment);
        $this->assertSame('confirmed', $appointment->status);
        $this->assertNull($appointment->payment_status);
        $this->assertSame('consultation', $appointment->visit_type);
        $this->assertNull($appointment->stripe_session_id);
        $this->assertSame('12:00:00', $appointment->ending_time);
        $this->assertSame($patient->id, $appointment->patient_id);

        $this->actingAs($patient)
            ->get(route('checkout.success', $appointment))
            ->assertOk()
            ->assertSee('Appointment Confirmed!');

        $this->assertNull($appointment->fresh()->payment_status);

        $this->actingAs($patient)
            ->get(route('patient.bookings'))
            ->assertOk()
            ->assertSee('Dr Maya Chen')
            ->assertSee('Confirmed');

        $this->actingAs($patient)
            ->get(route('patient.dashboard'))
            ->assertOk()
            ->assertSee('Dr Maya Chen')
            ->assertDontSee('Dr. Alfaz Ahmed');

        $this->actingAs($patient)
            ->post(route('patient.bookings.cancel', $appointment))
            ->assertSessionHasErrors('cancel_reason');

        $this->actingAs($patient)
            ->post(route('patient.bookings.cancel', $appointment), [
                'cancel_reason' => 'Need a different day',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Appointment cancelled.');

        $this->assertSame('Need a different day', $appointment->fresh()->cancel_reason);

        $this->assertSame('cancelled', $appointment->fresh()->status);

        $second = $this->makePatient();

        $this->actingAs($second)
            ->post(route('doctors.book.store', $doctor), $this->window($date, 'Follow up'))
            ->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'patient_id' => $second->id,
            'doctor_id' => $doctor->id,
            'status' => 'confirmed',
            'payment_status' => null,
        ]);
    }

    public function test_patient_can_open_reports_attached_to_a_booking(): void
    {
        Storage::fake('public');

        [, $doctor, $date] = $this->makeDoctor();
        $patient = $this->makePatient();
        $this->mockCheckout();

        $this->actingAs($patient)
            ->post(route('doctors.book.store', $doctor), array_merge($this->window($date), [
                'blood_reports' => [
                    UploadedFile::fake()->create('cbc-results.pdf', 20, 'application/pdf'),
                ],
                'xray_report_name' => 'Chest X-ray',
                'xray_reports' => [
                    UploadedFile::fake()->create('chest.png', 20, 'image/png'),
                ],
                'other_report_name' => 'Ultrasound',
                'other_reports' => [
                    UploadedFile::fake()->create('scan.pdf', 20, 'application/pdf'),
                ],
            ]))
            ->assertRedirect();

        $appointment = Appointment::query()->first();
        $report = $appointment->reports()->first();

        $this->assertNotNull($report);
        $this->assertSame('Blood Test Reports', $report->group_name);
        $this->assertSame('Chest X-ray', $appointment->reports()->where('original_name', 'chest.png')->value('group_name'));
        $this->assertSame('Ultrasound', $appointment->reports()->where('original_name', 'scan.pdf')->value('group_name'));

        $this->actingAs($patient)
            ->get(route('patient.bookings.show', $appointment))
            ->assertOk()
            ->assertSee('Blood Test Reports')
            ->assertSee('cbc-results.pdf')
            ->assertSee('Reports');

        $this->actingAs($patient)
            ->get(route('patient.bookings.reports.show', [$appointment, $report]))
            ->assertOk();

        $this->actingAs($this->makePatient())
            ->get(route('patient.bookings.reports.show', [$appointment, $report]))
            ->assertForbidden();
    }

    public function test_patient_cannot_book_a_taken_or_closed_slot(): void
    {
        [$doctorUser, $doctor, $date] = $this->makeDoctor();
        $first = $this->makePatient();
        $second = $this->makePatient();

        Appointment::query()->create([
            'patient_id' => $first->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $date,
            'appointment_time' => '10:00:00',
            'status' => 'pending',
            'visit_type' => 'consultation',
        ]);

        $this->actingAs($second)
            ->from(route('doctors.book', $doctor))
            ->post(route('doctors.book.store', $doctor), array_merge($this->window($date, 'Taken'), [
                'starting_time' => '10:00',
                'ending_time' => '10:30',
            ]))
            ->assertRedirect(route('doctors.book', $doctor))
            ->assertSessionHasErrors('starting_time');

        $this->assertDatabaseCount('appointments', 1);

        $closed = $this->makeDoctor([
            'is_available' => false,
        ]);

        $this->actingAs($second)
            ->from(route('doctors.book', $closed[1]))
            ->post(route('doctors.book.store', $closed[1]), $this->window($closed[2]))
            ->assertSessionHasErrors('starting_time');

        $this->assertDatabaseMissing('appointments', [
            'doctor_id' => $closed[1]->id,
        ]);

        $this->actingAs($doctorUser)
            ->get(route('doctor.dashboard'))
            ->assertOk()
            ->assertDontSee('John Smith');
    }

    public function test_doctor_cannot_change_another_doctors_appointment(): void
    {
        [, $doctor, $date] = $this->makeDoctor();
        [$other] = $this->makeDoctor();
        $patient = $this->makePatient();

        $appointment = Appointment::query()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $date,
            'appointment_time' => '10:00:00',
            'status' => 'pending',
            'visit_type' => 'consultation',
        ]);

        $this->actingAs($other)
            ->post(route('doctor.appointments.update-status', $appointment), [
                'status' => 'confirmed',
            ])
            ->assertForbidden();

        $this->assertSame('pending', $appointment->fresh()->status);

        $this->actingAs($doctor->user)
            ->post(route('doctor.appointments.update-status', $appointment), [
                'status' => 'confirmed',
            ])
            ->assertRedirect();

        $this->assertSame('confirmed', $appointment->fresh()->status);
    }

    public function test_guest_sees_login_and_a_doctor_cannot_book(): void
    {
        [$doctorUser, $doctor] = $this->makeDoctor();

        $this->get(route('doctors.book', $doctor))
            ->assertOk()
            ->assertSee('Continue to sign in')
            ->assertSee('name="blood_report_name"', false)
            ->assertSee('name="xray_report_name"', false)
            ->assertSee('name="other_report_name"', false)
            ->assertDontSee('Confirm appointment');

        $this->actingAs($doctorUser)
            ->get(route('doctors.book', $doctor))
            ->assertOk()
            ->assertSee('can’t book appointments')
            ->assertDontSee('Confirm appointment');
    }

    public function test_patient_login_restores_the_booking_details(): void
    {
        [$doctorUser, $doctor, $date] = $this->makeDoctor();
        $patient = $this->makePatient();

        Storage::fake('local');

        $this->post(route('doctors.book.remember', $doctor), [
            'appointment_date' => $date,
            'health_issues' => 'Morning headache',
            'symptom_duration' => 'Less than a day',
            'current_medicines' => 'Aspirin',
            'slot_day' => strtolower(now()->addDay()->format('l')),
            'starting_time' => '09:00',
            'ending_time' => '12:00',
            'blood_reports' => [
                UploadedFile::fake()->create('cbc-results.pdf', 20, 'application/pdf'),
            ],
        ])->assertRedirect(route('login'));

        $draft = session('booking_draft');

        $this->assertIsArray($draft);
        $this->assertArrayNotHasKey('blood_reports', $draft['input']);
        $this->assertSame('cbc-results.pdf', $draft['files'][0]['original_name']);
        Storage::disk('local')->assertExists($draft['files'][0]['path']);

        $this->followingRedirects()
            ->post('/login', [
                'email' => $patient->email,
                'password' => 'password',
            ])
            ->assertOk()
            ->assertSee('You are signed in')
            ->assertSee('Morning headache')
            ->assertSee('Aspirin')
            ->assertSee('Less than a day')
            ->assertSee('value="'.$date.'"', false)
            ->assertSee('value="09:00"', false);

        $this->post('/logout')->assertRedirect('/');

        $this->post(route('doctors.book.remember', $doctor), [
            'appointment_date' => $date,
            'health_issues' => 'Should stay off the doctor dashboard',
            'starting_time' => '09:00',
            'ending_time' => '12:00',
        ])->assertRedirect(route('login'));

        $this->post('/login', [
            'email' => $doctorUser->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_patient_can_book_next_month_and_the_visit_details_are_stored(): void
    {
        [, $doctor, $date] = $this->makeDoctor();
        $weekday = strtolower(Carbon::parse($date)->format('l'));
        $later = now()->startOfMonth()->addMonth();

        while (strtolower($later->format('l')) !== $weekday) {
            $later->addDay();
        }

        $patient = $this->makePatient();
        $this->mockCheckout();

        $this->actingAs($patient)
            ->post(route('doctors.book.store', $doctor), [
                'appointment_date' => $later->toDateString(),
                'slot_day' => $weekday,
                'starting_time' => '09:00',
                'ending_time' => '12:00',
                'health_issues' => 'Chest pain when climbing stairs',
                'symptom_duration' => '1–3 days',
                'current_medicines' => 'Aspirin 100mg',
            ])
            ->assertRedirect();

        $appointment = Appointment::query()->first();

        $this->assertNotNull($appointment);
        $this->assertSame($later->toDateString(), $appointment->appointment_date->toDateString());
        $this->assertSame('09:00:00', $appointment->appointment_time);
        $this->assertSame('12:00:00', $appointment->ending_time);
        $this->assertSame('confirmed', $appointment->status);
        $this->assertNull($appointment->payment_status);
        $this->assertStringContainsString('Chest pain when climbing stairs', (string) $appointment->reason);
        $this->assertStringContainsString('Duration: 1–3 days', (string) $appointment->reason);
        $this->assertStringContainsString('Medicines: Aspirin 100mg', (string) $appointment->reason);
    }

    public function test_verified_webhook_confirms_the_visit_and_a_cancel_frees_the_window(): void
    {
        [, $doctor, $date] = $this->makeDoctor();
        $patient = $this->makePatient();

        $appointment = Appointment::query()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $date,
            'appointment_time' => '09:00:00',
            'ending_time' => '12:00:00',
            'status' => 'pending',
            'visit_type' => 'consultation',
            'payment_status' => 'unpaid',
            'ticket_price' => 150,
            'stripe_session_id' => 'cs_paid',
        ]);

        $this->bindStripeEvent($this->stripeEvent('checkout.session.completed', $appointment, 15000));

        $this->postJson(route('stripe.webhook'), [])->assertOk();
        $this->postJson(route('stripe.webhook'), [])->assertOk();

        $appointment->refresh();
        $this->assertSame('paid', $appointment->payment_status);
        $this->assertSame('confirmed', $appointment->status);
        $this->assertNotNull($appointment->paid_at);

        $wrong = Appointment::query()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => now()->addDays(2)->toDateString(),
            'appointment_time' => '09:00:00',
            'ending_time' => '12:00:00',
            'status' => 'pending',
            'visit_type' => 'consultation',
            'payment_status' => 'unpaid',
            'ticket_price' => 150,
            'stripe_session_id' => 'cs_wrong_amount',
        ]);

        $this->bindStripeEvent($this->stripeEvent('checkout.session.completed', $wrong, 100));

        $this->postJson(route('stripe.webhook'), [])->assertOk();
        $this->assertSame('unpaid', $wrong->fresh()->payment_status);

        $held = Appointment::query()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => now()->addDays(3)->toDateString(),
            'appointment_time' => '09:00:00',
            'ending_time' => '12:00:00',
            'status' => 'pending',
            'visit_type' => 'consultation',
            'payment_status' => 'unpaid',
            'ticket_price' => 150,
            'stripe_session_id' => 'cs_expired',
        ]);

        $this->bindStripeEvent($this->stripeEvent('checkout.session.expired', $held, 15000));

        $this->postJson(route('stripe.webhook'), [])->assertOk();
        $this->assertSame('cancelled', $held->fresh()->status);
        $this->assertSame('failed', $held->fresh()->payment_status);

        $abandoned = Appointment::query()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $date,
            'appointment_time' => '11:00:00',
            'ending_time' => '11:30:00',
            'status' => 'pending',
            'visit_type' => 'consultation',
            'payment_status' => 'unpaid',
            'ticket_price' => 150,
            'stripe_session_id' => 'cs_cancel',
        ]);

        $this->actingAs($patient)
            ->get(route('doctors.book', $doctor).'?checkout=cancel&session_id=cs_cancel')
            ->assertOk()
            ->assertSee('Payment was cancelled');

        $this->assertSame('cancelled', $abandoned->fresh()->status);
    }

    public function test_a_free_visit_is_confirmed_without_stripe(): void
    {
        [, $doctor, $date] = $this->makeDoctor();
        $doctor->update(['ticket_price' => 0]);
        $patient = $this->makePatient();

        $this->mock(StripeCheckout::class, function ($mock) {
            $mock->shouldReceive('createSession')->never();
        });

        $response = $this->actingAs($patient)
            ->post(route('doctors.book.store', $doctor), $this->window($date));

        $appointment = Appointment::query()->first();

        $response->assertRedirect(route('checkout.success', $appointment));
        $this->assertSame('confirmed', $appointment->status);
        $this->assertNull($appointment->payment_status);
        $this->assertNull($appointment->stripe_session_id);

        $this->actingAs($patient)
            ->get(route('checkout.success', $appointment))
            ->assertOk()
            ->assertSee('Appointment Confirmed!')
            ->assertSee('0.00 USD');
    }

    public function test_receptionist_books_a_confirmed_visit_and_cannot_use_the_patient_form(): void
    {
        [, $doctor, $date] = $this->makeDoctor();
        $patient = $this->makePatient();
        $receptionist = User::factory()->create([
            'role' => 'receptionist',
        ]);
        $receptionist->receptionist()->create([
            'doctor_id' => $doctor->id,
        ]);

        $this->actingAs($receptionist)
            ->post(route('doctors.book.store', $doctor), $this->window($date))
            ->assertForbidden();

        $this->actingAs($receptionist)
            ->post(route('receptionist.appointments.store'), [
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'date' => $date,
                'slot' => '09:00|12:00',
                'reason' => 'Desk booking',
                'visit_type' => 'check-up',
            ])
            ->assertRedirect(route('receptionist.appointments'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => 'confirmed',
            'visit_type' => 'check-up',
        ]);

        $this->actingAs($receptionist)
            ->get(route('receptionist.dashboard'))
            ->assertOk()
            ->assertDontSee('John Smith');
    }

    public function test_booking_header_uses_submitted_reviews_instead_of_the_catalog_rating(): void
    {
        [, $doctor] = $this->makeDoctor();
        $doctor->forceFill([
            'average_rating' => 5,
            'total_rating' => 1,
        ])->save();

        $this->get(route('doctors.book', $doctor))
            ->assertOk()
            ->assertSee('>0.0<', false)
            ->assertDontSee('>5.0<', false);

        $patient = $this->makePatient();
        DoctorReview::query()->create([
            'doctor_id' => $doctor->id,
            'user_id' => $patient->id,
            'rating' => 4,
            'body' => 'Clear and kind.',
        ]);

        $this->get(route('doctors.book', $doctor))
            ->assertOk()
            ->assertSee('>4.0<', false)
            ->assertSee('>1</span>', false)
            ->assertDontSee('>5.0<', false);

        $this->getJson(route('doctors.reviews.index', $doctor))
            ->assertOk()
            ->assertJsonPath('average', '4.0')
            ->assertJsonPath('total', 1);

        $this->assertEquals(5.0, (float) $doctor->fresh()->average_rating);
        $this->assertSame(1, $doctor->fresh()->total_rating);
    }

    /**
     * @return array<string, string>
     */
    private function window(string $date, string $issues = 'Headache'): array
    {
        return [
            'appointment_date' => $date,
            'slot_day' => strtolower(Carbon::parse($date)->format('l')),
            'starting_time' => '09:00',
            'ending_time' => '12:00',
            'health_issues' => $issues,
        ];
    }

    private int $checkoutCount = 0;

    private ?object $stripeEventPayload = null;

    private function mockCheckout(): void
    {
        $this->mock(StripeCheckout::class, function ($mock) {
            $mock->shouldReceive('createSession')->andReturnUsing(function () {
                $this->checkoutCount++;

                return (object) [
                    'id' => 'cs_test_'.$this->checkoutCount,
                    'url' => 'https://checkout.stripe.com/c/pay/cs_test_'.$this->checkoutCount,
                ];
            });
        });
    }

    private function bindStripeEvent(object $event): void
    {
        $this->stripeEventPayload = $event;

        $this->mock(StripeCheckout::class, function ($mock) {
            $mock->shouldReceive('constructEvent')->andReturnUsing(function () {
                return $this->stripeEventPayload;
            });
        });
    }

    private function stripeEvent(string $type, Appointment $appointment, int $amount): object
    {
        return (object) [
            'type' => $type,
            'data' => (object) [
                'object' => (object) [
                    'id' => $appointment->stripe_session_id,
                    'client_reference_id' => (string) $appointment->id,
                    'amount_total' => $amount,
                    'currency' => 'usd',
                    'payment_intent' => 'pi_123',
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $slotOverrides
     * @return array{0: User, 1: Doctor, 2: string}
     */
    private function makeDoctor(array $slotOverrides = []): array
    {
        $date = now()->addDay()->toDateString();
        $weekday = strtolower(now()->addDay()->format('l'));

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

        TimeSlot::query()->create(array_merge([
            'doctor_id' => $doctor->id,
            'day' => $weekday,
            'starting_time' => '09:00',
            'ending_time' => '12:00',
            'is_available' => true,
        ], $slotOverrides));

        return [$user->fresh(), $doctor->fresh(), $date];
    }

    private function makePatient(): User
    {
        return User::factory()->create([
            'role' => 'patient',
            'is_active' => true,
        ]);
    }
}
