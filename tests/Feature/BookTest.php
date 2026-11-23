<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
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

        $response = $this->get('/books/'.$book->id);

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

        $response = $this->actingAs($user)->get('/books/'.$book->id.'/edit');

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

        $response = $this->actingAs($otherUser)->get('/books/'.$book->id.'/edit');

        $response->assertForbidden();
    }

    // 登録者本人が書籍を削除できること
    public function test_book_owner_can_delete_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->delete('/books/'.$book->id);

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

        $response = $this->actingAs($otherUser)->delete('/books/'.$book->id);

        $response->assertForbidden();
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    // 書籍削除時に関連データも削除されること
    public function test_deleting_book_also_deletes_related_data(): void
    {
        $owner = User::factory()->create();
        $reader = User::factory()->create();
        $liker = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        // 書籍にレビューを登録する
        $review = Review::factory()->create([
            'user_id' => $reader->id,
            'book_id' => $book->id,
        ]);

        // レビューにいいねを登録する
        $liker->reviewLikes()->create([
            'review_id' => $review->id,
        ]);

        // 書籍をお気に入りに登録する
        $reader->favorites()->create([
            'book_id' => $book->id,
        ]);

        // 読書計画を登録する
        $reader->readingPlans()->create([
            'book_id' => $book->id,
            'target_date' => '2026-12-01',
            'status' => 'in_progress',
        ]);

        // 書籍の登録者が書籍を削除する
        $response = $this->actingAs($owner)->delete('/books/'.$book->id);

        $response->assertRedirect('/books');

        // 書籍と関連データが削除されていること
        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);

        $this->assertDatabaseMissing('review_likes', [
            'review_id' => $review->id,
        ]);

        $this->assertDatabaseMissing('favorites', [
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseMissing('reading_plans', [
            'book_id' => $book->id,
        ]);
    }

    // 登録者本人が書籍を更新できること
    public function test_book_owner_can_update_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => '更新後ジャンル']);

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->put(
            '/books/'.$book->id,
            [
                'title' => '更新後の書籍名',
                'author' => '更新後の著者',
                'isbn' => '9781234567890',
                'published_date' => '2025-05-01',
                'description' => '更新後の説明',
                'genres' => [$genre->id],
            ]
        );

        $response->assertRedirect('/books/'.$book->id);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後の書籍名',
            'author' => '更新後の著者',
        ]);
    }

    // 他のユーザーは書籍を更新できないこと
    public function test_other_user_cannot_update_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'title' => '元の書籍名',
        ]);

        $response = $this->actingAs($otherUser)->put(
            '/books/'.$book->id,
            [
                'title' => '変更を試みた書籍名',
                'author' => '別の著者',
                'isbn' => '9781234567890',
                'published_date' => '2025-05-01',
            ]
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '元の書籍名',
        ]);
    }

    // タイトルまたは著者の一部が一致する書籍を検索できること
    public function test_books_can_be_searched_by_keyword(): void
    {
        $user = User::factory()->create();

        // タイトルで検索される書籍を登録する
        $titleBook = Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => '山田太郎',
        ]);

        // 著者で検索される書籍を登録する
        $authorBook = Book::factory()->create([
            'title' => 'PHP実践ガイド',
            'author' => '佐藤花子',
        ]);

        // 検索に一致しない書籍を登録する
        Book::factory()->create([
            'title' => 'Java入門',
            'author' => '鈴木一郎',
        ]);

        // キーワードで書籍を検索する
        $response = $this->get('/books?keyword=Laravel');

        // タイトルが一致する書籍だけが取得されることを確認する
        $response->assertViewHas('books', function ($books) use ($titleBook) {
            return $books->pluck('id')->contains($titleBook->id)
                && $books->count() === 1;
        });

        // 著者名でも検索できることを確認する
        $response = $this->get('/books?keyword=佐藤');

        $response->assertViewHas('books', function ($books) use ($authorBook) {
            return $books->pluck('id')->contains($authorBook->id)
                && $books->count() === 1;
        });
    }

    // ジャンル検索とキーワード・ジャンルの組み合わせ検索ができること
    public function test_books_can_be_searched_by_genre_and_combined_conditions(): void
    {
        // 検索対象となるジャンルを作成する
        $novelGenre = Genre::create(['name' => '小説']);
        $businessGenre = Genre::create(['name' => 'ビジネス']);

        // 小説ジャンルに属する検索対象の書籍を作成する
        $targetBook = Book::factory()->create([
            'title' => 'Laravel小説',
        ]);
        $targetBook->genres()->attach($novelGenre->id);

        // 同じジャンルだがキーワードが一致しない書籍を作成する
        $otherBook = Book::factory()->create([
            'title' => 'PHP入門',
        ]);
        $otherBook->genres()->attach($novelGenre->id);

        // キーワードは一致するが別ジャンルの書籍を作成する
        $differentGenreBook = Book::factory()->create([
            'title' => 'Laravel実践',
        ]);
        $differentGenreBook->genres()->attach($businessGenre->id);

        // ジャンルだけで検索できることを確認する
        $response = $this->get('/books?genre='.$novelGenre->id);

        $response->assertViewHas('books', function ($books) use ($targetBook, $otherBook) {
            return $books->pluck('id')->contains($targetBook->id)
                && $books->pluck('id')->contains($otherBook->id)
                && $books->count() === 2;
        });

        // キーワードとジャンルの両方に一致する書籍だけが取得されることを確認する
        $response = $this->get('/books?keyword=Laravel&genre='.$novelGenre->id);

        $response->assertViewHas('books', function ($books) use ($targetBook) {
            return $books->pluck('id')->contains($targetBook->id)
                && $books->count() === 1;
        });
    }

    // 書籍を指定した条件で並べ替えられること
    public function test_books_can_be_sorted_by_selected_condition(): void
    {
        // テスト用ユーザーを作成する
        $user = User::factory()->create();

        // 登録日時の異なる書籍を作成する
        $oldBook = Book::factory()->create([
            'title' => 'C書籍',
            'created_at' => now()->subDays(3),
        ]);

        $newBook = Book::factory()->create([
            'title' => 'A書籍',
            'created_at' => now(),
        ]);

        $middleBook = Book::factory()->create([
            'title' => 'B書籍',
            'created_at' => now()->subDay(),
        ]);

        // 評価順を確認するため各書籍にレビューを登録する
        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $oldBook->id,
            'rating' => 3,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $newBook->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $middleBook->id,
            'rating' => 4,
        ]);

        // 新しい順に並ぶことを確認する
        $response = $this->get('/books?sort=newest');

        $response->assertViewHas('books', function ($books) use ($newBook, $middleBook, $oldBook) {
            return $books->pluck('id')->all() === [
                $newBook->id,
                $middleBook->id,
                $oldBook->id,
            ];
        });

        // 古い順に並ぶことを確認する
        $response = $this->get('/books?sort=oldest');

        $response->assertViewHas('books', function ($books) use ($newBook, $middleBook, $oldBook) {
            return $books->pluck('id')->all() === [
                $oldBook->id,
                $middleBook->id,
                $newBook->id,
            ];
        });

        // 評価の高い順に並ぶことを確認する
        $response = $this->get('/books?sort=rating');

        $response->assertViewHas('books', function ($books) use ($newBook, $middleBook, $oldBook) {
            return $books->pluck('id')->all() === [
                $newBook->id,
                $middleBook->id,
                $oldBook->id,
            ];
        });

        // タイトルの昇順に並ぶことを確認する
        $response = $this->get('/books?sort=title');

        $response->assertViewHas('books', function ($books) use ($newBook, $middleBook, $oldBook) {
            return $books->pluck('id')->all() === [
                $newBook->id,
                $middleBook->id,
                $oldBook->id,
            ];
        });
    }

    // ページネーションで10件ずつ表示し検索条件を引き継ぐこと
    public function test_pagination_preserves_search_and_sort_conditions(): void
    {
        // 検索対象となる書籍を11件作成する
        for ($i = 1; $i <= 11; $i++) {
            Book::factory()->create([
                'title' => 'Laravel書籍'.$i,
            ]);
        }

        // 1ページ目に10件表示されることを確認する
        $response = $this->get('/books?keyword=Laravel&sort=title');

        $response->assertViewHas('books', function ($books) {
            return $books->count() === 10
                && $books->total() === 11
                && str_contains($books->url(2), 'keyword=Laravel')
                && str_contains($books->url(2), 'sort=title');
        });

        // 2ページ目にも検索条件が引き継がれることを確認する
        $response = $this->get('/books?keyword=Laravel&sort=title&page=2');

        $response->assertViewHas('books', function ($books) {
            return $books->count() === 1
                && $books->currentPage() === 2
                && $books->total() === 11;
        });
    }

    // ISBNからGoogle Books APIの書籍情報を取得できること
    public function test_book_information_can_be_searched_by_isbn(): void
    {
        // 認証ユーザーを作成する
        $user = User::factory()->create();

        // Google Books APIの成功レスポンスを再現する
        Http::fake([
            'www.googleapis.com/*' => Http::response([
                'totalItems' => 1,
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'テスト書籍',
                            'authors' => ['山田太郎'],
                            'description' => '書籍の説明',
                            'imageLinks' => [
                                'thumbnail' => 'https://example.com/image.jpg',
                            ],
                            'publishedDate' => '2025-01-01',
                        ],
                    ],
                ],
            ], 200),
        ]);

        // ISBNを指定して書籍情報を検索する
        $response = $this->actingAs($user)
            ->getJson('/books/isbn/9784101010014');

        // 取得した書籍情報が正しく返されることを確認する
        $response->assertOk()
            ->assertJson([
                'title' => 'テスト書籍',
                'author' => '山田太郎',
                'description' => '書籍の説明',
                'image_url' => 'https://example.com/image.jpg',
                'published_date' => '2025-01-01',
            ]);
    }

    // ISBNの形式が不正な場合はエラーを返すこと
    public function test_invalid_isbn_returns_validation_error(): void
    {
        // 認証ユーザーを作成する
        $user = User::factory()->create();

        // 13桁ではないISBNで検索する
        $response = $this->actingAs($user)
            ->getJson('/books/isbn/123456789012');

        // 422エラーとエラーメッセージを確認する
        $response->assertStatus(422)
            ->assertJson([
                'error' => 'ISBNは13桁の数字で入力してください。',
            ]);
    }

    // 該当する書籍が見つからない場合はエラーを返すこと
    public function test_isbn_search_returns_not_found_when_book_does_not_exist(): void
    {
        // 認証ユーザーを作成する
        $user = User::factory()->create();

        // Google Books APIの検索結果が0件の場合を再現する
        Http::fake([
            'www.googleapis.com/*' => Http::response([
                'totalItems' => 0,
                'items' => [],
            ], 200),
        ]);

        // ISBNで書籍情報を検索する
        $response = $this->actingAs($user)
            ->getJson('/books/isbn/9780000000002');

        // 404エラーとエラーメッセージを確認する
        $response->assertNotFound()
            ->assertJson([
                'error' => '書籍情報が見つかりませんでした。',
            ]);
    }

    // Google Books APIの利用制限に達した場合は429エラーを返すこと
    public function test_isbn_search_returns_error_when_api_quota_is_exceeded(): void
    {
        // 認証ユーザーを作成する
        $user = User::factory()->create();

        // Google Books APIの429レスポンスを再現する
        Http::fake([
            'www.googleapis.com/*' => Http::response([
                'error' => ['code' => 429],
            ], 429),
        ]);

        // ISBNで書籍情報を検索する
        $response = $this->actingAs($user)
            ->getJson('/books/isbn/9784101010014');

        // 429エラーとエラーメッセージを確認する
        $response->assertStatus(429)
            ->assertJson([
                'error' => '現在、書籍情報を取得できません。時間をおいて再度お試しください。',
            ]);
    }

    // Google Books APIでエラーが発生した場合は503エラーを返すこと
    public function test_isbn_search_returns_error_when_api_fails(): void
    {
        // 認証ユーザーを作成する
        $user = User::factory()->create();

        // Google Books APIの500レスポンスを再現する
        Http::fake([
            'www.googleapis.com/*' => Http::response([
                'error' => ['code' => 500],
            ], 500),
        ]);

        // ISBNで書籍情報を検索する
        $response = $this->actingAs($user)
            ->getJson('/books/isbn/9784101010014');

        // 503エラーとエラーメッセージを確認する
        $response->assertStatus(503)
            ->assertJson([
                'error' => '書籍情報の取得に失敗しました。時間をおいて再度お試しください。',
            ]);
    }

    // Google Books APIとの通信に失敗した場合は503エラーを返すこと
    public function test_isbn_search_returns_error_when_connection_fails(): void
    {
        // 認証ユーザーを作成する
        $user = User::factory()->create();

        // Google Books APIとの通信失敗を再現する
        Http::fake([
            'www.googleapis.com/*' => function () {
                throw new ConnectionException('Connection timeout');
            },
        ]);

        // ISBNで書籍情報を検索する
        $response = $this->actingAs($user)
            ->getJson('/books/isbn/9784101010014');

        // 503エラーとエラーメッセージを確認する
        $response->assertStatus(503)
            ->assertJson([
                'error' => '通信に失敗しました。時間をおいて再度お試しください。',
            ]);
    }
}
