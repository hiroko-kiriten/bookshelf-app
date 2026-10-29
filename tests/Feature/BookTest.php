<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    // 書籍一覧を表示できること
    public function test_books_index_can_be_displayed(): void
    {
        Book::factory()->create();

        $response = $this->get('/books');

        $response->assertOk();
    }

    // 書籍詳細を表示できること
    public function test_book_detail_can_be_displayed(): void
    {
        $book = Book::factory()->create();

        $response = $this->get('/books/' . $book->id);

        $response->assertOk();
    }

    // ログインユーザーが書籍を登録できること
    public function test_authenticated_user_can_create_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => 'テストジャンル']);

        $response = $this->actingAs($user)->post('/books', [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
            'published_date' => '2025-01-01',
            'description' => 'テスト説明',
            'genres' => [$genre->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('books', [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
            'user_id' => $user->id,
        ]);
    }

    // 必須項目が不足している場合は書籍を登録できないこと
    public function test_book_cannot_be_created_without_required_fields(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => 'テストジャンル']);

        $response = $this->actingAs($user)->post('/books', [
            'title' => '',
            'author' => '',
            'isbn' => '',
            'published_date' => '',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors([
            'title',
            'author',
            'isbn',
            'published_date',
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    // ISBNが重複している場合は書籍を登録できないこと
    public function test_book_cannot_be_created_with_duplicate_isbn(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => 'テストジャンル']);

        Book::factory()->create([
            'isbn' => '9781234567890',
        ]);

        $response = $this->actingAs($user)->post('/books', [
            'title' => '別の書籍',
            'author' => '別の著者',
            'isbn' => '9781234567890',
            'published_date' => '2025-01-01',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors('isbn');
        $this->assertDatabaseCount('books', 1);
    }

    // 登録者本人が書籍を編集できること
    public function test_book_owner_can_edit_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/books/' . $book->id . '/edit');

        $response->assertOk();
    }

    // 他のユーザーは書籍を編集できないこと
    public function test_other_user_cannot_edit_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)->get('/books/' . $book->id . '/edit');

        $response->assertForbidden();
    }

    // 登録者本人が書籍を削除できること
    public function test_book_owner_can_delete_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->delete('/books/' . $book->id);

        $response->assertRedirect('/books');
        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    // 他のユーザーは書籍を削除できないこと
    public function test_other_user_cannot_delete_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)->delete('/books/' . $book->id);

        $response->assertForbidden();
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }
}