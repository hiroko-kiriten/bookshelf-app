<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    // お気に入り一覧を表示できること
    public function test_authenticated_user_can_view_favorites(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/favorites');

        $response->assertOk();
    }

    // ログインユーザーがお気に入りに登録できること
    public function test_user_can_add_book_to_favorites(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(
             '/favorites/' . $book->id . '/toggle'
        );

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    // 登録済みのお気に入りを解除できること
    public function test_user_can_remove_book_from_favorites(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $user->favorites()->create([
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)->post(
            '/favorites/' . $book->id . '/toggle'
    );    

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    // 未ログインユーザーはお気に入り一覧を表示できないこと
    public function test_guest_cannot_view_favorites(): void
    {
        $response = $this->get('/favorites');

        $response->assertRedirect('/login');
    }

    // 未ログインユーザーはお気に入り登録できないこと
    public function test_guest_cannot_add_book_to_favorites(): void
    {
        $book = Book::factory()->create();

        $response = $this->post('/favorites/' . $book->id . '/toggle');

        $response->assertRedirect('/login');

        $this->assertDatabaseCount('favorites', 0);
    }
}