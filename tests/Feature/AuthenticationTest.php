<?php

namespace Tests\Feature;

use App\Infrastructure\Security\AdminToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['jwt.secret' => str_repeat('s', 64)]);
    }

    public function test_admin_can_obtain_a_signed_token(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $response = $this->postJson('/api/auth/token', [
            'email' => $user->email, 'password' => 'password',
        ])->assertOk()->assertJsonPath('token_type', 'Bearer')->assertJsonPath('expires_in', 3600);
        $this->assertSame($user->id, app(AdminToken::class)->userId($response->json('access_token')));
    }

    public function test_wrong_password_and_non_admin_have_the_same_denial(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $response = $this->postJson('/api/auth/token', [
            'email' => $user->email, 'password' => 'wrong',
        ])->assertUnauthorized();
        $user->forceFill(['is_admin' => false])->save();
        $this->postJson('/api/auth/token', ['email' => $user->email, 'password' => 'password'])
            ->assertUnauthorized()->assertExactJson($response->json());
        $this->postJson('/api/auth/token', ['email' => 'missing@example.test', 'password' => 'wrong'])
            ->assertUnauthorized()->assertExactJson($response->json());
    }
}
