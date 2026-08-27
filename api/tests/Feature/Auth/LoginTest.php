<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_credentials_return_a_token(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correct-password')]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $response->assertOk()->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
    }

    public function test_invalid_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correct-password')]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_unknown_email_is_rejected(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nobody@example.test',
            'password' => 'whatever',
        ]);

        $response->assertStatus(422);
    }

    public function test_the_returned_token_authenticates_subsequent_requests(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correct-password')]);

        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->json('token');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/user')
            ->assertOk()
            ->assertJson(['id' => $user->id, 'email' => $user->email]);
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }
}
