<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    // ログインユーザーがレビューを投稿できること
    public function test_authenticated_user_can_create_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(
            '/books/'.$book->id.'/reviews',
            [
                'rating' => 5,
                'comment' => 'とても面白い本でした。',
            ]
        );

        $response->assertRedirect('/books/'.$book->id);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても面白い本でした。',
        ]);
    }

    // 評価が1〜5の範囲外なら投稿できないこと
    public function test_review_cannot_be_created_with_invalid_rating(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(
            '/books/'.$book->id.'/reviews',
            [
                'rating' => 6,
                'comment' => 'テストコメント',
            ]
        );

        $response->assertSessionHasErrors('rating');
        $this->assertDatabaseCount('reviews', 0);
    }

    // コメントが未入力なら投稿できないこと
    public function test_review_cannot_be_created_without_comment(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(
            '/books/'.$book->id.'/reviews',
            [
                'rating' => 4,
                'comment' => '',
            ]
        );

        $response->assertSessionHasErrors('comment');
        $this->assertDatabaseCount('reviews', 0);
    }

    // 同じ書籍にレビューを重複投稿できないこと
    public function test_user_cannot_review_the_same_book_twice(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)->post(
            '/books/'.$book->id.'/reviews',
            [
                'rating' => 3,
                'comment' => '2回目のレビュー',
            ]
        );

        $response->assertSessionHasErrors('review');
        $this->assertDatabaseCount('reviews', 1);
    }

    // 投稿者本人がレビュー編集画面を表示できること
    public function test_review_owner_can_access_edit_page(): void
    {
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(
            '/reviews/'.$review->id.'/edit'
        );

        $response->assertOk();
    }

    // 他のユーザーはレビューを編集できないこと
    public function test_other_user_cannot_access_review_edit_page(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)->get(
            '/reviews/'.$review->id.'/edit'
        );

        $response->assertForbidden();
    }

    // 投稿者本人がレビューを更新できること
    public function test_review_owner_can_update_review(): void
    {
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->put(
            '/reviews/'.$review->id,
            [
                'rating' => 4,
                'comment' => '更新したレビューです。',
            ]
        );

        $response->assertRedirect('/books/'.$review->book_id);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 4,
            'comment' => '更新したレビューです。',
        ]);
    }

    // 他のユーザーはレビューを更新できないこと
    public function test_other_user_cannot_update_review(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $owner->id,
            'rating' => 5,
            'comment' => '元のレビュー',
        ]);

        $response = $this->actingAs($otherUser)->put(
            '/reviews/'.$review->id,
            [
                'rating' => 1,
                'comment' => '変更を試みたレビュー',
            ]
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'comment' => '元のレビュー',
        ]);
    }

    // 投稿者本人がレビューを削除できること
    public function test_review_owner_can_delete_review(): void
    {
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->delete(
            '/reviews/'.$review->id
        );

        $response->assertRedirect('/books/'.$review->book_id);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
    }

    // 他のユーザーはレビューを削除できないこと
    public function test_other_user_cannot_delete_review(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)->delete(
            '/reviews/'.$review->id
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
        ]);
    }
}
