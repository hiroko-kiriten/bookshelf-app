<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ログイン画面が表示されること
    public function test_login_page_can_be_displayed(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
    }

    // 正しい認証情報でログインできること
    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    // 誤った認証情報ではログインできないこと
    public function test_user_cannot_login_with_incorrect_password(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    // ログアウトできること
    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
    }

    // 未ログインでは書籍登録画面にアクセスできないこと
    public function test_guest_cannot_access_book_creation_page(): void
    {
        $response = $this->get('/books/create');

        $response->assertRedirect('/login');
    }
}