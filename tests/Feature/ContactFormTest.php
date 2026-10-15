<?php

namespace Tests\Feature;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.contact.inbox' => 'support@medicare.test']);
    }

    public function test_contact_page_shows_a_form(): void
    {
        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('Send a message')
            ->assertSee('name="message"', false)
            ->assertDontSee('This site does not publish a phone number');
    }

    public function test_a_visitor_can_send_a_message(): void
    {
        Mail::fake();

        $this->post(route('contact.store'), [
            'name' => 'Asha Menon',
            'email' => 'asha@example.com',
            'phone' => '9876543210',
            'subject' => 'booking',
            'message' => 'I need help finding an open time next week.',
        ])
            ->assertRedirect(route('contact'))
            ->assertSessionHas('status', 'Thanks. We received your message and will reply by email.');

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'Asha Menon',
            'email' => 'asha@example.com',
            'phone' => '9876543210',
            'subject' => 'booking',
            'body' => 'I need help finding an open time next week.',
            'user_id' => null,
        ]);

        Mail::assertSent(ContactMessageReceived::class, function (ContactMessageReceived $mail) {
            return $mail->hasTo('support@medicare.test')
                && $mail->hasReplyTo('asha@example.com')
                && $mail->contactMessage->subjectLabel() === 'Booking a visit';
        });
    }

    public function test_a_signed_in_patient_is_attached_to_the_message(): void
    {
        Mail::fake();

        $patient = User::factory()->create([
            'role' => 'patient',
            'name' => 'Ravi Shah',
            'email' => 'ravi@example.com',
        ]);

        $this->actingAs($patient)
            ->post(route('contact.store'), [
                'name' => 'Ravi Shah',
                'email' => 'ravi@example.com',
                'subject' => 'account',
                'message' => 'I cannot see my last visit on the dashboard.',
            ])
            ->assertRedirect(route('contact'));

        $this->assertDatabaseHas('contact_messages', [
            'user_id' => $patient->id,
            'email' => 'ravi@example.com',
            'subject' => 'account',
        ]);
    }

    public function test_incomplete_messages_are_rejected(): void
    {
        Mail::fake();

        $this->from(route('contact'))
            ->post(route('contact.store'), [
                'name' => '',
                'email' => 'not-an-email',
                'subject' => 'unknown',
                'message' => 'Too short',
            ])
            ->assertRedirect(route('contact'))
            ->assertSessionHasErrors(['name', 'email', 'subject', 'message']);

        $this->assertSame(0, ContactMessage::query()->count());
        Mail::assertNothingSent();
    }

    public function test_a_filled_honeypot_is_ignored(): void
    {
        Mail::fake();

        $this->post(route('contact.store'), [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'subject' => 'other',
            'message' => 'This is an automated advertisement.',
            'company' => 'Spam Co',
        ])
            ->assertRedirect(route('contact'))
            ->assertSessionHas('status');

        $this->assertSame(0, ContactMessage::query()->count());
        Mail::assertNothingSent();
    }
}
