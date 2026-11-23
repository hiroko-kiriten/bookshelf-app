<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\View\View;

class ReportController extends Controller
{
    // ログインユーザーの読書レポートを表示する
    public function index(): View
    {
        $user = auth()->user();

        // ログインユーザーのレビューと書籍・ジャンル情報を取得する
        $reviews = $user->reviews()
            ->with([
                'book' => function ($query) {
                    $query->withCount('reviews')
                        ->with('genres');
                },
            ])
            ->get();

        // 基本集計
        $totalReviews = $reviews->count();
        $averageRating = $reviews->avg('rating') ?? 0;

        // 評価別の件数（評価1～5）
        $ratingDistribution = collect(range(1, 5))
            ->map(function ($rating) use ($reviews) {
                return $reviews->where('rating', $rating)->count();
            })
            ->values();

        // 読了冊数（読了済みの書籍を重複なく集計）
        $booksRead = $user->readingPlans()
            ->where('status', 'completed')
            ->distinct()
            ->count('book_id');

        // 高評価書籍TOP5（評価4以上）
        $topRatedBooks = $reviews
            ->filter(function ($review) {
                return $review->rating >= 4;
            })
            ->sort(function ($a, $b) {
                // 評価の高い順
                if ($a->rating !== $b->rating) {
                    return $b->rating <=> $a->rating;
                }

                // 書籍のレビュー件数が多い順
                if ($a->book->reviews_count !== $b->book->reviews_count) {
                    return $b->book->reviews_count <=> $a->book->reviews_count;
                }

                // 最新のレビューが先
                return $b->created_at <=> $a->created_at;
            })
            ->take(5)
            ->map(function ($review) {
                return [
                    'id' => $review->book->id,
                    'title' => $review->book->title,
                    'author' => $review->book->author,
                    'rating' => $review->rating,
                ];
            })
            ->values();

        // ジャンルごとにレビューを集計する
        $genreReviews = [];

        foreach ($reviews as $review) {
            foreach ($review->book->genres as $genre) {
                $genreReviews[$genre->id][] = $review;
            }
        }

        // レビューが0件のジャンルも含めて集計する
        $allGenres = Genre::all();

        $genreRatings = $allGenres
            ->map(function ($genre) use ($genreReviews) {
                $genreReviewList = collect($genreReviews[$genre->id] ?? []);
                $count = $genreReviewList->count();

                return [
                    'id' => $genre->id,
                    'name' => $genre->name,
                    'count' => $count,
                    'average_rating' => $count > 0
                        ? $genreReviewList->avg('rating')
                        : 0.0,
                    'latest_review_at' => $genreReviewList->max('created_at'),
                ];
            })
            ->sort(function ($a, $b) {
                // レビューがあるジャンルを先に表示
                if ($a['count'] === 0 && $b['count'] > 0) {
                    return 1;
                }

                if ($a['count'] > 0 && $b['count'] === 0) {
                    return -1;
                }

                // 平均評価が高い順
                if ($a['average_rating'] != $b['average_rating']) {
                    return $b['average_rating'] <=> $a['average_rating'];
                }

                // レビュー件数が多い順
                if ($a['count'] !== $b['count']) {
                    return $b['count'] <=> $a['count'];
                }

                // 最新レビューが先
                $latestA = $a['latest_review_at']?->timestamp ?? 0;
                $latestB = $b['latest_review_at']?->timestamp ?? 0;

                return $latestB <=> $latestA;
            });

        // ジャンル数が5以下なら全件、6以上ならTOP5
        $genreLimit = $allGenres->count() <= 5
            ? $allGenres->count()
            : 5;

        $genreRatings = $genreRatings
            ->take($genreLimit)
            ->map(function ($genre) {
                unset($genre['latest_review_at']);

                return $genre;
            })
            ->values();

        $stats = [
            'summary' => [
                'total_reviews' => $totalReviews,
                'books_read' => $booksRead,
                'average_rating' => $averageRating,
            ],
            'rating_distribution' => $ratingDistribution,
            'top_rated_books' => $topRatedBooks,
            'genre_ratings' => $genreRatings,
        ];

        return view('reports.index', compact('stats'));
    }
}
