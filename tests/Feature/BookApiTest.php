<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
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

        $response = $this->getJson('/api/v1/books/'.$book->id);

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
            '/api/v1/books/'.$book->id.'/reviews'
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

    // 認証済みユーザーがAPIで書籍を登録できること
    public function test_authenticated_user_can_create_book_through_api(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $genre = Genre::create([
            'name' => 'APIテストジャンル',
        ]);

        $response = $this->withToken($token)->postJson('/api/v1/books', [
            'title' => 'API登録テスト',
            'author' => 'テスト著者',
            'isbn' => '9784123456789',
            'published_date' => '2026-01-01',
            'genres' => [$genre->id],
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => 'API登録テスト',
            'isbn' => '9784123456789',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'genre_id' => $genre->id,
        ]);
    }

    // 未認証ユーザーはAPIで書籍を登録できないこと
    public function test_guest_cannot_create_book_through_api(): void
    {
        $response = $this->postJson('/api/v1/books', []);

        $response->assertUnauthorized();
    }

    // 認証済みの所有者がAPIで書籍を更新できること
    public function test_authenticated_owner_can_update_book_through_api(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::create([
            'name' => 'APIテストジャンル',
        ]);

        $response = $this->withToken($token)->putJson('/api/v1/books/'.$book->id, [
            'title' => 'API更新テスト',
            'author' => '更新後の著者',
            'isbn' => $book->isbn,
            'published_date' => '2026-02-01',
            'genres' => [$genre->id],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $user->id,
            'title' => 'API更新テスト',
            'author' => '更新後の著者',
        ]);
    }

    // 他人の書籍はAPIで更新できないこと
    public function test_authenticated_non_owner_cannot_update_book_through_api(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $token = $otherUser->createToken('test-token')->plainTextToken;

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $genre = Genre::create([
            'name' => 'APIテストジャンル',
        ]);

        $response = $this->withToken($token)->putJson('/api/v1/books/'.$book->id, [
            'title' => '不正な更新テスト',
            'author' => '更新後の著者',
            'isbn' => $book->isbn,
            'published_date' => '2026-02-01',
            'genres' => [$genre->id],
        ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $owner->id,
            'title' => $book->title,
        ]);
    }

    // 未認証ユーザーはAPIで書籍を更新できないこと
    public function test_guest_cannot_update_book_through_api(): void
    {
        $book = Book::factory()->create();

        $response = $this->putJson('/api/v1/books/'.$book->id, []);

        $response->assertUnauthorized();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    // 未認証ユーザーはAPIで書籍を削除できないこと
    public function test_guest_cannot_delete_book_through_api(): void
    {
        $book = Book::factory()->create();

        $response = $this->deleteJson('/api/v1/books/'.$book->id);

        $response->assertUnauthorized();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    // 他人の書籍はAPIで削除できないこと
    public function test_authenticated_non_owner_cannot_delete_book_through_api(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $token = $otherUser->createToken('test-token')->plainTextToken;

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->withToken($token)
            ->deleteJson('/api/v1/books/'.$book->id);

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $owner->id,
        ]);
    }

    // 認証済みの所有者がAPIで書籍と関連データを削除できること
    public function test_authenticated_owner_can_delete_book_through_api(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::create([
            'name' => 'APIテストジャンル',
        ]);

        $book->genres()->attach($genre);

        $review = Review::factory()->create([
            'book_id' => $book->id,
            'user_id' => $user->id,
        ]);

        $response = $this->withToken($token)
            ->deleteJson('/api/v1/books/'.$book->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    // キーワードでタイトル・著者を部分一致検索できること
    public function test_public_api_can_search_books_by_keyword(): void
    {
        $book1 = Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => '山田太郎',
        ]);

        $book2 = Book::factory()->create([
            'title' => 'PHP実践',
            'author' => 'Laravel研究会',
        ]);

        Book::factory()->create([
            'title' => 'Ruby入門',
            'author' => '佐藤花子',
        ]);

        $response = $this->getJson('/api/v1/books?keyword=Laravel');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $book1->id])
            ->assertJsonFragment(['id' => $book2->id]);
    }

    // ジャンルIDで書籍を絞り込めること
    public function test_public_api_can_filter_books_by_genre(): void
    {
        $genre = Genre::create(['name' => 'API絞り込みテスト']);
        $otherGenre = Genre::create(['name' => '別ジャンル']);

        $book1 = Book::factory()->create();
        $book1->genres()->attach($genre->id);

        $book2 = Book::factory()->create();
        $book2->genres()->attach($otherGenre->id);

        $response = $this->getJson(
            '/api/v1/books?genre_id='.$genre->id
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $book1->id]);
    }

    // ページネーションが正しく動作すること
    public function test_public_api_can_paginate_books(): void
    {
        Book::factory()->count(22)->create();

        $response = $this->getJson(
            '/api/v1/books?per_page=5&page=2'
        );

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 22);
    }

    // 不正な検索パラメータはバリデーションエラーになること
    public function test_public_api_rejects_invalid_search_parameters(): void
    {
        $response = $this->getJson(
            '/api/v1/books?genre_id=99999&per_page=101'
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['genre_id', 'per_page']);
    }

    // 無効化されたトークンではAPIで書籍を登録できないこと
    public function test_invalidated_token_cannot_create_book_through_api(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        // 発行したAPIトークンを無効化する
        $token->accessToken->delete();

        $response = $this->withToken($token->plainTextToken)
            ->postJson('/api/v1/books', []);

        $response->assertUnauthorized();
    }
}
