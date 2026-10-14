<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        Storage::fake('public');

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
            'phone' => '1234567890',
            'age' => '30',
            'role' => 'patient',
            'gender' => 'male',
            'photo' => UploadedFile::fake()->image('face.jpg'),
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::query()->where('email', 'test@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('patient', $user->role);
        $this->assertNotNull($user->slug);
        $this->assertMatchesRegularExpression(
            '#^storage/photos/patient/\d{14}(_\d+)?\.jpg$#',
            $user->photo,
        );
        Storage::disk('public')->assertExists(substr($user->photo, strlen('storage/')));
        $this->assertDatabaseHas('patient_profiles', [
            'user_id' => $user->id,
            'age' => 30,
        ]);
    }

    public function test_doctors_register_as_pending(): void
    {
        $this->post('/register', [
            'name' => 'Dr Test',
            'email' => 'doctor@example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
            'phone' => '1234567890',
            'age' => '40',
            'role' => 'doctor',
            'gender' => 'female',
        ]);

        $user = User::query()->where('email', 'doctor@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('doctor', $user->role);
        $this->assertDatabaseHas('doctors', [
            'user_id' => $user->id,
            'approval_status' => 'pending',
        ]);
        $this->assertDatabaseMissing('patient_profiles', [
            'user_id' => $user->id,
        ]);
    }

    public function test_registration_rejects_privileged_roles(): void
    {
        $this->post('/register', [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
            'phone' => '1234567890',
            'age' => '40',
            'role' => 'admin',
            'gender' => 'male',
        ])->assertSessionHasErrors('role');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', [
            'email' => 'admin@example.com',
        ]);
    }

    public function test_registration_rejects_invalid_fields(): void
    {
        $this->post('/register', [
            'name' => '123',
            'email' => 'not-an-email',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '12ab',
            'age' => 'two',
            'role' => '',
            'gender' => '',
        ])->assertSessionHasErrors(['name', 'email', 'password', 'phone', 'age', 'role', 'gender']);

        $this->assertGuest();
    }
}
