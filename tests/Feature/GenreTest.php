<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreTest extends TestCase
{
    use RefreshDatabase;

    // ジャンル一覧を表示できること
    public function test_genre_list_can_be_displayed(): void
{
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/genres');

    $response->assertOk();
}

    // ジャンル登録画面を表示できること
    public function test_genre_creation_page_can_be_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/genres/create');

        $response->assertOk();
    }

    // ログインユーザーがジャンルを登録できること
    public function test_user_can_create_genre(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/genres', [
            'name' => 'ミステリー',
        ]);

        $this->assertDatabaseHas('genres', [
            'name' => 'ミステリー',
        ]);
    }

    // ジャンル詳細を表示できること
    public function test_genre_detail_can_be_displayed(): void
{
    $user = User::factory()->create();

    $genre = Genre::create([
        'name' => 'ミステリー',
    ]);

    $response = $this->actingAs($user)->get('/genres/' . $genre->id);

    $response->assertOk();
}

    // ジャンル編集画面を表示できること
    public function test_genre_edit_page_can_be_displayed(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => 'ミステリー',
        ]);

        $response = $this->actingAs($user)->get(
            '/genres/' . $genre->id . '/edit'
        );

        $response->assertOk();
    }

    // ログインユーザーがジャンルを更新できること
    public function test_user_can_update_genre(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => 'ミステリー',
        ]);

        $response = $this->actingAs($user)->put(
            '/genres/' . $genre->id,
            [
                'name' => 'ファンタジー',
            ]
        );

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => 'ファンタジー',
        ]);
    }

    // ログインユーザーがジャンルを削除できること
    public function test_user_can_delete_genre(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => 'ミステリー',
        ]);

        $response = $this->actingAs($user)->delete(
            '/genres/' . $genre->id
        );

        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);
    }

    // 未ログインユーザーはジャンル登録画面を表示できないこと
    public function test_guest_cannot_access_genre_creation_page(): void
    {
        $response = $this->get('/genres/create');

        $response->assertRedirect('/login');
    }
}