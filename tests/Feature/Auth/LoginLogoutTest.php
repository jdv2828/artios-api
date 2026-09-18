<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LoginLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_login_and_receive_a_token(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@turnero.com',
            'name' => 'Admin',
            'password' => 'secret',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret',
        ]);

        $response->assertOk();
        $response->assertJsonPath('user.email', 'admin@turnero.com');
        $response->assertJsonStructure([
            'user' => ['id', 'name', 'email', 'role'],
            'token_type',
            'access_token',
        ]);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'admin@turnero.com',
            'name' => 'Admin',
            'password' => 'secret',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'admin@turnero.com',
            'password' => 'wrong',
        ])->assertUnauthorized();
    }

    public function test_employee_can_logout_with_sanctum_token(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@turnero.com',
            'name' => 'Admin',
            'password' => 'secret',
        ]);

        $token = $user->createToken('auth')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
