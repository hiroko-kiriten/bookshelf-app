<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    // 公開APIで書籍一覧を取得できること
    public function test_public_api_can_get_books(): void
    {
        $user = User::factory()->create();

        Book::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->getJson('/api/v1/books');

        $response->assertOk();
    }

    // 公開APIで書籍詳細を取得できること
    public function test_public_api_can_get_book_detail(): void
    {
        $book = Book::factory()->create();

        $response = $this->getJson('/api/v1/books/' . $book->id);

        $response->assertOk();
    }

    // 公開APIで書籍のレビュー一覧を取得できること
    public function test_public_api_can_get_book_reviews(): void
    {
        $book = Book::factory()->create();
        $user = User::factory()->create();

        Review::factory()->create([
            'book_id' => $book->id,
            'user_id' => $user->id,
        ]);

        $response = $this->getJson(
            '/api/v1/books/' . $book->id . '/reviews'
        );

        $response->assertOk();
    }

    // 存在しない書籍の詳細は404を返すこと
    public function test_public_api_returns_404_for_missing_book(): void
    {
        $response = $this->getJson('/api/v1/books/99999');

        $response->assertNotFound();
    }

    // 未ログインでも公開APIを利用できること
    public function test_guest_can_access_public_api(): void
    {
        $response = $this->getJson('/api/v1/books');

        $response->assertOk();
    }
}