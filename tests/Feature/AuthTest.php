<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_displayed(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_user_can_login(): void
    {
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->postJson('/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['user', 'redirect']);
    }

    public function test_read_only_user_cannot_access_admin(): void
    {
        $user = User::create([
            'name' => 'Read Only',
            'email' => 'readonly@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_READ_ONLY,
        ]);

        $this->actingAs($user, 'web');

        $response = $this->get('/auth/users');
        $response->assertStatus(302);
    }

    public function test_admin_can_create_user(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin, 'web');

        $response = $this->postJson('/auth/register', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => User::ROLE_FULL,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['user', 'message']);
    }

    public function test_admin_can_reset_user_password_and_user_must_change_it(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-reset@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_ADMIN,
        ]);
        $user = User::create([
            'name' => 'User',
            'email' => 'reset-user@example.com',
            'password' => Hash::make('old-password'),
            'role' => User::ROLE_READ_ONLY,
        ]);

        $this->actingAs($admin, 'web');

        $reset = $this->postJson("/auth/users/{$user->id}/reset-password");
        $reset->assertOk()->assertJsonStructure(['temporary_password']);

        $temporaryPassword = $reset->json('temporary_password');
        $this->assertTrue(Hash::check($temporaryPassword, $user->fresh()->password));
        $this->assertTrue((bool) $user->fresh()->must_change_password);

        $login = $this->postJson('/auth/login', [
            'email' => $user->email,
            'password' => $temporaryPassword,
        ]);
        $login->assertOk()->assertJsonPath('must_change_password', true);

        $this->postJson('/auth/user', [
            'name' => $user->name,
            'email' => $user->email,
            'current_password' => $temporaryPassword,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ])->assertOk();

        $this->assertFalse((bool) $user->fresh()->must_change_password);
    }
}
