<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    // 読書レポートを表示できること
    public function test_user_can_view_reading_report(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();
        $response->assertViewIs('reports.index');
        $response->assertViewHas('stats');
    }

    // 未ログインユーザーは読書レポートを表示できないこと
    public function test_guest_cannot_view_reading_report(): void
    {
        $response = $this->get(route('reports.index'));

        $response->assertRedirect('/login');
    }

    // レビュー件数・評価分布・読了冊数を正しく集計できること
    public function test_report_summary_and_rating_distribution_are_correct(): void
    {
        $user = User::factory()->create();

        $book1 = Book::factory()->create();
        $book2 = Book::factory()->create();
        $book3 = Book::factory()->create();

        // 自分のレビューを3件登録
        foreach ([
            [$book1, 5],
            [$book2, 4],
            [$book3, 2],
        ] as [$book, $rating]) {
            Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => $rating,
            ]);
        }

        // 他人のレビューは集計に含めない
        Review::factory()->create([
            'rating' => 1,
        ]);

        // 読了済み2冊、進行中1冊を登録
        $user->readingPlans()->createMany([
            [
                'book_id' => $book1->id,
                'target_date' => today(),
                'status' => ReadingPlanStatus::COMPLETED,
                'completed_at' => now(),
            ],
            [
                'book_id' => $book2->id,
                'target_date' => today(),
                'status' => ReadingPlanStatus::COMPLETED,
                'completed_at' => now(),
            ],
            [
                'book_id' => $book3->id,
                'target_date' => today()->addDays(7),
                'status' => ReadingPlanStatus::IN_PROGRESS,
            ],
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();

        $stats = $response->viewData('stats');

        // レビュー件数
        $this->assertSame(3, $stats['summary']['total_reviews']);

        // 平均評価（5 + 4 + 2）÷ 3
        $this->assertEquals(11 / 3, $stats['summary']['average_rating']);

        // 読了冊数
        $this->assertSame(2, $stats['summary']['books_read']);

        // 評価1～5の件数
        $this->assertSame([0, 1, 0, 1, 1], $stats['rating_distribution']->all());
    }

    // 高評価書籍を評価・レビュー件数・最新レビュー日時の順に取得できること
    public function test_top_rated_books_are_sorted_correctly(): void
    {
        $user = User::factory()->create();

        $book1 = Book::factory()->create(['title' => '評価5の書籍']);
        $book2 = Book::factory()->create(['title' => 'レビュー数が多い書籍']);
        $book3 = Book::factory()->create(['title' => '最新レビューの書籍']);
        $book4 = Book::factory()->create(['title' => '評価3の書籍']);

        // 評価5
        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'rating' => 5,
            'created_at' => now()->subDays(3),
        ]);

        // 評価4・書籍全体のレビュー件数が多い
        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book2->id,
            'rating' => 4,
            'created_at' => now()->subDays(2),
        ]);

        Review::factory()->count(2)->create([
            'book_id' => $book2->id,
            'rating' => 3,
        ]);

        // 評価4・レビュー件数は同じだが、最新のレビュー
        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book3->id,
            'rating' => 4,
            'created_at' => now()->subDay(),
        ]);

        // 評価3なので対象外
        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book4->id,
            'rating' => 3,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();

        $topRatedBooks = $response->viewData('stats')['top_rated_books'];

        $this->assertSame(
            ['評価5の書籍', 'レビュー数が多い書籍', '最新レビューの書籍'],
            $topRatedBooks->pluck('title')->all()
        );
    }

    // ジャンル別の平均評価・件数・並び順を正しく集計できること
    public function test_genre_ratings_are_aggregated_and_sorted_correctly(): void
    {
        $user = User::factory()->create();

        $genre1 = Genre::create(['name' => 'ミステリー']);
        $genre2 = Genre::create(['name' => 'ファンタジー']);
        $genre3 = Genre::create(['name' => '歴史']);
        $genre4 = Genre::create(['name' => '未レビュー']);

        $book1 = Book::factory()->create();
        $book2 = Book::factory()->create();
        $book3 = Book::factory()->create();

        $book1->genres()->attach($genre1->id);
        $book2->genres()->attach($genre2->id);
        $book3->genres()->attach($genre3->id);

        // ミステリー：平均5.0、1件
        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'rating' => 5,
        ]);

        // ファンタジー：平均4.5、2件
        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book2->id,
            'rating' => 4,
        ]);

        // 歴史：平均3.0、1件
        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book3->id,
            'rating' => 3,
        ]);

        // 他人のレビューは集計に含めない
        $otherUser = User::factory()->create();

        Review::factory()->create([
            'user_id' => $otherUser->id,
            'book_id' => $book3->id,
            'rating' => 5,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();

        $genreRatings = $response->viewData('stats')['genre_ratings'];

        $this->assertSame(
            ['ミステリー', 'ファンタジー', '歴史', '未レビュー'],
            $genreRatings->pluck('name')->all()
        );

        $this->assertSame(
            [1, 1, 1, 0],
            $genreRatings->pluck('count')->all()
        );

        $this->assertEquals(
            [5.0, 4.0, 3.0, 0.0],
            $genreRatings->pluck('average_rating')->all()
        );
    }

    // ジャンルが6件以上ある場合はTOP5だけ表示すること
    public function test_genre_ratings_are_limited_to_top_five(): void
    {
        $user = User::factory()->create();

        // 6ジャンルを作成
        $genres = collect();

        for ($i = 1; $i <= 6; $i++) {
            $genres->push(
                Genre::create(['name' => 'ジャンル'.$i])
            );
        }

        // 各ジャンルに書籍とレビューを登録
        foreach ($genres as $index => $genre) {
            $book = Book::factory()->create();

            $book->genres()->attach($genre->id);

            $review = Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => $index + 1 > 5 ? 5 : $index + 1,
            ]);

            // ジャンル6のレビューを最も新しくする
            if ($index === 5) {
                $review->forceFill([
                    'created_at' => now()->addSecond(),
                ])->saveQuietly();
            }

        }

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();

        $genreRatings = $response->viewData('stats')['genre_ratings'];

        // 表示件数は5件
        $this->assertCount(5, $genreRatings);

        // 評価の高い5ジャンルが表示される
        $this->assertSame(
            ['ジャンル6', 'ジャンル5', 'ジャンル4', 'ジャンル3', 'ジャンル2'],
            $genreRatings->pluck('name')->all()
        );
    }

    // レポート対象のデータがない場合に空データ用の表示になること
    public function test_reading_report_displays_empty_data_messages(): void
    {
        // テスト用ユーザーを作成する
        $user = User::factory()->create();

        // レビューなどのデータがない状態でレポートを表示する
        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        // レポート画面が正常に表示されることを確認する
        $response->assertOk();

        // 集計値がすべて初期値になっていることを確認する
        $response->assertViewHas('stats', function ($stats) {
            return $stats['summary']['total_reviews'] === 0
                && $stats['summary']['books_read'] === 0
                && $stats['summary']['average_rating'] === 0
                && $stats['top_rated_books']->isEmpty()
                && $stats['genre_ratings']->isEmpty();
        });

        // 高評価書籍とジャンル別評価の空データメッセージを確認する
        $response->assertSeeText('4星以上の書籍がありません');
        $response->assertSeeText('ジャンルが設定された書籍のレビューがありません');
    }
}
