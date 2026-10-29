<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewLikeTest extends TestCase
{
    use RefreshDatabase;

    // ログインユーザーがレビューにいいねできること
    public function test_user_can_like_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $reviewer = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $reviewer->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)->post(
            '/reviews/' . $review->id . '/like'
        );

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

    // いいね済みのレビューに再度操作するといいねを解除できること
    public function test_user_can_unlike_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $reviewer = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $reviewer->id,
            'book_id' => $book->id,
        ]);

        $user->reviewLikes()->create([
            'review_id' => $review->id,
        ]);

        $response = $this->actingAs($user)->post(
            '/reviews/' . $review->id . '/like'
        );

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

    // 未ログインユーザーはレビューにいいねできないこと
    public function test_guest_cannot_like_review(): void
    {
        $book = Book::factory()->create();
        $reviewer = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $reviewer->id,
            'book_id' => $book->id,
        ]);

        $response = $this->post(
            '/reviews/' . $review->id . '/like'
        );

        $response->assertRedirect('/login');

        $this->assertDatabaseCount('review_likes', 0);
    }
}
