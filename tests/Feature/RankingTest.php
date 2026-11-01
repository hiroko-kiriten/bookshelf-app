<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Review;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    // ランキング画面を表示できること
    public function test_ranking_page_can_be_displayed(): void
    {
        $response = $this->get('/ranking');

        $response->assertOk();
    }

    // ログインしていないユーザーもランキングを表示できること
    public function test_guest_can_view_ranking(): void
    {
        $response = $this->get('/ranking');

        $response->assertOk();
    }

    // 書籍が存在する場合もランキングを表示できること
    public function test_ranking_page_can_be_displayed_with_books(): void
    {
        $user = User::factory()->create();

        Book::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->get('/ranking');

        $response->assertOk();
    }

    // 評価の高い順に表示し、レビューのない書籍は除外すること
public function test_ranking_is_sorted_by_rating_and_excludes_books_without_reviews(): void
{
    $owner = User::factory()->create();
    $reviewer = User::factory()->create();

    $lowRatedBook = Book::factory()->create([
        'user_id' => $owner->id,
        'title' => '低評価書籍',
    ]);

    $highRatedBook = Book::factory()->create([
        'user_id' => $owner->id,
        'title' => '高評価書籍',
    ]);

    Book::factory()->create([
        'user_id' => $owner->id,
        'title' => 'レビューなし書籍',
    ]);

    Review::factory()->create([
        'user_id' => $reviewer->id,
        'book_id' => $lowRatedBook->id,
        'rating' => 2,
    ]);

    Review::factory()->create([
        'user_id' => $reviewer->id,
        'book_id' => $highRatedBook->id,
        'rating' => 5,
    ]);

    $response = $this->get('/ranking');

    $response->assertOk();
    $response->assertSeeInOrder([
        '高評価書籍',
        '低評価書籍',
    ]);
    $response->assertDontSee('レビューなし書籍');
}

 // ランキングは最大10冊まで表示されること
public function test_ranking_displays_only_top_10_books(): void
{
    $owner = User::factory()->create();
    $reviewer = User::factory()->create();

    for ($i = 1; $i <= 11; $i++) {
        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'title' => sprintf('ランキング対象書籍 No.%02d', $i),
            'created_at' => now()->subDays(12 - $i),
        ]);

        Review::factory()->create([
            'user_id' => $reviewer->id,
            'book_id' => $book->id,
            'rating' => 5,
        ]);
    }

    $response = $this->get('/ranking');

    $response->assertOk();

    for ($i = 2; $i <= 11; $i++) {
        $response->assertSee(sprintf('No.%02d', $i));
    }

    $response->assertDontSee('No.01');
}
}