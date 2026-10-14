<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\DoctorReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_patient_publishes_a_review_and_later_updates_it(): void
    {
        [$doctorUser, $doctor] = $this->makeDoctor();
        $patient = User::factory()->create([
            'role' => 'patient',
            'name' => 'Asha Patel',
        ]);
        $other = User::factory()->create([
            'role' => 'patient',
            'name' => 'Liam Ortiz',
        ]);

        $this->actingAs($patient)
            ->get(route('doctors.show', $doctorUser->slug))
            ->assertOk()
            ->assertSee('Submit feedback')
            ->assertSee('How would you rate the overall experience?')
            ->assertDontSee('Public reviews are not collected on this site yet.');

        $this->actingAs($patient)
            ->postJson(route('doctors.reviews.store', $doctor), [
                'rating' => 5,
                'reviewText' => 'Clear and kind.',
            ])
            ->assertOk()
            ->assertJsonPath('review.body', 'Clear and kind.')
            ->assertJsonPath('review.name', 'Asha Patel')
            ->assertJsonPath('average', '5.0')
            ->assertJsonPath('total', 1)
            ->assertJsonPath('updated', false);

        $doctor->refresh();
        $this->assertSame(1, $doctor->total_rating);
        $this->assertEquals(5.0, (float) $doctor->average_rating);
        $this->assertSame(1, DoctorReview::query()->count());

        $this->actingAs($other)
            ->postJson(route('doctors.reviews.store', $doctor), [
                'rating' => 3,
                'reviewText' => 'The visit ran late.',
            ])
            ->assertOk()
            ->assertJsonPath('average', '4.0')
            ->assertJsonPath('total', 2);

        $this->actingAs($patient)
            ->postJson(route('doctors.reviews.store', $doctor), [
                'rating' => 1,
                'reviewText' => 'I would ask more questions next time.',
            ])
            ->assertOk()
            ->assertJsonPath('updated', true)
            ->assertJsonPath('average', '2.0')
            ->assertJsonPath('total', 2);

        $this->assertSame(1, DoctorReview::query()->where('user_id', $patient->id)->count());

        $feed = $this->getJson(route('doctors.reviews.index', $doctor))
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('average', '2.0')
            ->assertJsonPath('reviews.0.body', 'The visit ran late.')
            ->assertJsonPath('reviews.1.body', 'I would ask more questions next time.');

        $feed->assertDontSee($patient->email);

        $this->get(route('doctors.show', $doctorUser->slug))
            ->assertOk()
            ->assertSee('I would ask more questions next time.')
            ->assertSee('The visit ran late.')
            ->assertSee('2.0')
            ->assertSee('Sign in to submit');

        $this->actingAs($doctorUser)
            ->get(route('doctor.reviews'))
            ->assertOk()
            ->assertSee('I would ask more questions next time.')
            ->assertSee('The visit ran late.')
            ->assertSee('2.0')
            ->assertDontSee('name="reviewText"', false);
    }

    public function test_guests_and_other_roles_cannot_publish_a_review(): void
    {
        [$doctorUser, $doctor] = $this->makeDoctor();
        $doctorViewer = User::factory()->create(['role' => 'doctor']);
        $receptionist = User::factory()->create(['role' => 'receptionist']);

        $this->get(route('doctors.show', $doctorUser->slug))
            ->assertOk()
            ->assertSee('Sign in to submit')
            ->assertSee('No reviews yet.');

        $this->post(route('doctors.reviews.store', $doctor), [
            'rating' => 4,
            'reviewText' => 'Saved until I sign in.',
        ])->assertRedirect(route('doctors.reviews.begin', $doctor));

        $this->assertSame(0, DoctorReview::query()->count());

        $this->get(route('doctors.reviews.begin', $doctor))
            ->assertRedirect(route('login'));

        $this->assertSame(
            route('doctors.show', ['slug' => $doctorUser->slug, 'feedback' => 1]),
            session('url.intended')
        );

        $patient = User::factory()->create([
            'role' => 'patient',
            'name' => 'Asha Patel',
        ]);

        $this->actingAs($patient)
            ->get(route('doctors.show', ['slug' => $doctorUser->slug, 'feedback' => 1]))
            ->assertOk()
            ->assertSee('Saved until I sign in.')
            ->assertSee('Submit feedback');

        $this->actingAs($doctorViewer)
            ->get(route('doctors.show', $doctorUser->slug))
            ->assertOk()
            ->assertSee('Patients write reviews on this profile.')
            ->assertDontSee('name="reviewText"', false);

        $this->actingAs($doctorViewer)
            ->postJson(route('doctors.reviews.store', $doctor), [
                'rating' => 5,
                'reviewText' => 'Not a patient.',
            ])
            ->assertForbidden();

        $this->actingAs($receptionist)
            ->postJson(route('doctors.reviews.store', $doctor), [
                'rating' => 5,
                'reviewText' => 'Not a patient.',
            ])
            ->assertForbidden();

        $this->actingAs($patient)
            ->postJson(route('doctors.reviews.store', $doctor), [
                'rating' => 0,
                'reviewText' => '',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rating', 'reviewText']);
    }

    public function test_feedback_shows_ten_reviews_per_page_and_opens_the_form_in_a_dialog(): void
    {
        [$doctorUser, $doctor] = $this->makeDoctor();

        foreach (range(1, 12) as $number) {
            $patient = User::factory()->create([
                'role' => 'patient',
                'name' => 'Patient '.$number,
            ]);

            $this->actingAs($patient)
                ->postJson(route('doctors.reviews.store', $doctor), [
                    'rating' => 5,
                    'reviewText' => $number === 1
                        ? 'Oldest visit note'
                        : ($number === 12 ? 'Newest visit note' : 'Middle visit '.$number),
                ])
                ->assertOk();
        }

        $this->getJson(route('doctors.reviews.index', $doctor))
            ->assertOk()
            ->assertJsonCount(10, 'reviews')
            ->assertJsonPath('page', 1)
            ->assertJsonPath('lastPage', 2)
            ->assertJsonPath('perPage', 10)
            ->assertJsonPath('reviews.0.body', 'Newest visit note')
            ->assertJsonMissing(['body' => 'Oldest visit note']);

        $this->getJson(route('doctors.reviews.index', ['doctor' => $doctor, 'page' => 2]))
            ->assertOk()
            ->assertJsonCount(2, 'reviews')
            ->assertJsonPath('page', 2)
            ->assertJsonPath('reviews.1.body', 'Oldest visit note');

        auth()->logout();

        $this->get(route('doctors.show', $doctorUser->slug))
            ->assertOk()
            ->assertSee('Newest visit note')
            ->assertSee('Middle visit 3')
            ->assertDontSee('Oldest visit note')
            ->assertSee('Write a review')
            ->assertSee('role="dialog"', false)
            ->assertSee('Next');

        $this->get(route('doctors.show', ['slug' => $doctorUser->slug, 'feedback' => 1, 'reviews_page' => 2]))
            ->assertOk()
            ->assertSee('Oldest visit note')
            ->assertDontSee('Newest visit note')
            ->assertSee('Previous');
    }

    /**
     * @return array{0: User, 1: Doctor}
     */
    private function makeDoctor(): array
    {
        $user = User::factory()->create([
            'name' => 'Dr John Smith',
            'role' => 'doctor',
            'slug' => 'dr-john-smith',
            'is_active' => true,
        ]);

        $doctor = Doctor::query()->create([
            'user_id' => $user->id,
            'specialization' => 'Cardiology',
            'ticket_price' => 50,
            'bio' => 'Heart care',
            'approval_status' => 'approved',
        ]);

        return [$user, $doctor];
    }
}
