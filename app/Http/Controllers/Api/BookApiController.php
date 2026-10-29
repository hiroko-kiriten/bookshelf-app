<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookResource;
use App\Http\Resources\ReviewResource;
use App\Models\Book;

class BookApiController extends Controller
{
    // 書籍一覧を取得する
    public function index()
    {
        $books = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->orderByDesc('created_at')
            ->get();

        return BookResource::collection($books);
    }

    // 書籍詳細を取得する
    public function show(Book $book)
    {
       $book->load([
    'genres',
    'reviews.user',
]);

$book->reviews->each(function ($review) {
    $review->loadCount('likes');
});

        $book->loadAvg('reviews', 'rating');
        $book->loadCount('reviews');

        return (new BookResource($book))->additional([
            'reviews' => ReviewResource::collection($book->reviews),
        ]);
    }
}