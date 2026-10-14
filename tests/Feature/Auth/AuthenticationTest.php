<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_login_does_not_return_a_patient_to_the_doctor_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'patient',
        ]);

        $this->get('/doctor/dashboard')->assertRedirect(route('login'));

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->get(route('dashboard'))
            ->assertRedirect(route('patient.dashboard'));

        $this->get(route('patient.dashboard'))->assertOk();
    }

    public function test_a_doctor_without_a_profile_can_open_the_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'doctor',
            'is_active' => true,
        ]);

        $this->get('/doctor/dashboard')->assertRedirect(route('login'));

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/doctor/dashboard');

        $this->get('/doctor/dashboard')
            ->assertOk();

        $this->assertDatabaseHas('doctors', [
            'user_id' => $user->id,
            'approval_status' => 'pending',
        ]);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
