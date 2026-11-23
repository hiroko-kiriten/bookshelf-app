<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTokenTest extends TestCase
{
    use RefreshDatabase;

    // 現在使用中のAPIトークンだけを無効化できること
    public function test_user_can_revoke_current_api_token(): void
    {
        $user = User::factory()->create();

        $tokenA = $user->createToken('token-a');
        $tokenB = $user->createToken('token-b');

        $response = $this->withToken($tokenA->plainTextToken)
            ->deleteJson('/api/v1/token');

        $response->assertOk()
            ->assertJson([
                'message' => 'APIトークンを無効化しました。',
            ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenA->accessToken->id,
        ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $tokenB->accessToken->id,
        ]);

        // 別のトークンでは認証が引き続き利用できること
        $this->withToken($tokenB->plainTextToken)
            ->getJson('/api/user')
            ->assertOk();
    }

    // 未認証ユーザーはAPIトークンを無効化できないこと
    public function test_guest_cannot_revoke_api_token(): void
    {
        $response = $this->deleteJson('/api/v1/token');

        $response->assertUnauthorized();
    }

    // 無効化したトークンでは認証が必要なAPIを利用できないこと
    public function test_revoked_token_cannot_access_protected_api(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $this->withToken($token->plainTextToken)
            ->deleteJson('/api/v1/token')
            ->assertOk();
        $this->app['auth']->forgetGuards();

        $this->withToken($token->plainTextToken)
            ->getJson('/api/user')
            ->assertUnauthorized();
    }
}
