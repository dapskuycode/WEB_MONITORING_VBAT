<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token(): void
    {
        $user = User::factory()->create([
            'email'    => 'test@example.com',
            'password' => Hash::make('password123'),
            'role'     => 'student',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'user'  => ['id', 'name', 'email', 'role'],
                    'token',
                    'type',
                ],
            ])
            ->assertJsonPath('data.type', 'Bearer');
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email'    => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_logout_revokes_token(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');
        $response->assertStatus(401);
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create([
            'email'    => 'me@example.com',
            'role'     => 'super_admin',
            'password' => Hash::make('password123'),
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', 'me@example.com')
            ->assertJsonPath('data.role', 'super_admin');
    }

    public function test_me_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/auth/me');
        $response->assertStatus(401);
    }

    public function test_admin_endpoints_require_super_admin_role(): void
    {
        $student = User::factory()->create([
            'role'     => 'student',
            'password' => Hash::make('password123'),
        ]);
        $token = $student->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/admin/notifications');

        $response->assertStatus(403);
    }

    public function test_sponsor_blocked_from_analytics_endpoints(): void
    {
        $sponsor = User::factory()->create([
            'role'     => 'sponsor',
            'password' => Hash::make('password123'),
        ]);
        $token = $sponsor->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/admin/analytics/dashboard');

        $response->assertStatus(403);
    }

    public function test_cross_user_access_blocked(): void
    {
        $userA = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);
        $userB = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $tokenA = $userA->createToken('test')->plainTextToken;

        // User A cannot access user B notifications (404 = not found because scope hides it)
        $notificationB = \App\Models\Notification::create([
            'recipient_type' => User::class,
            'recipient_id'   => $userB->id,
            'type'           => 'test',
            'title'          => 'Test',
            'body'           => 'Body',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->postJson("/api/v1/user/notifications/{$notificationB->id}/read");

        // Cross-user access blocked via scoping: notification is "not found" for user A
        $response->assertStatus(404);
    }
}
