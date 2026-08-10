<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_sanctum_token(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('api-token')->plainTextToken;

        $this->assertIsString($token);
        $this->assertStringContainsString('|', $token);
    }

    public function test_user_can_register_and_receive_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Taylor',
            'email' => 'taylor@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.user.email', 'taylor@example.com')
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'taylor@example.com',
        ]);
    }

    public function test_user_can_login_and_receive_token(): void
    {
        User::factory()->create([
            'email' => 'taylor@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'taylor@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.user.email', 'taylor@example.com')
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token',
                ],
            ]);
    }

    public function test_authenticated_user_can_get_me(): void
    {
        $user = User::factory()->create([
            'email' => 'taylor@example.com',
        ]);

        $token = $user->createToken('api-token');

        $this->withHeader('Authorization', 'Bearer ' . $token->plainTextToken)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'taylor@example.com');
    }

    public function test_authenticated_user_can_logout_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token');

        $this->withHeader('Authorization', 'Bearer ' . $token->plainTextToken)
            ->postJson('/api/auth/logout')
            ->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }
}
