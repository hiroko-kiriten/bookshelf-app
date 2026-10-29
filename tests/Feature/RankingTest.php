<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
}
