<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IndexBookApiRequest;
use App\Http\Requests\Api\StoreBookApiRequest;
use App\Http\Requests\Api\UpdateBookApiRequest;
use App\Http\Resources\BookResource;
use App\Http\Resources\ReviewResource;
use App\Models\Book;
use Illuminate\Support\Facades\DB;

class BookApiController extends Controller
{
/**
 * 書籍一覧を取得する
 */
public function index(IndexBookApiRequest $request)
{
    $validated = $request->validated();

    $books = Book::with('genres')
        ->withAvg('reviews', 'rating')
        ->withCount('reviews')
        ->when($validated['keyword'] ?? null, function ($query, $keyword) {
            $query->where(function ($query) use ($keyword) {
                $query->where('title', 'like', "%{$keyword}%")
                    ->orWhere('author', 'like', "%{$keyword}%");
            });
        })
        ->when($validated['genre_id'] ?? null, function ($query, $genreId) {
            $query->whereHas('genres', function ($query) use ($genreId) {
                $query->where('genres.id', $genreId);
            });
        })
        ->orderByDesc('created_at')
        ->paginate($validated['per_page'] ?? 20);

    return BookResource::collection($books);
}

    /**
     * 書籍詳細を取得する
     */
    public function show(Book $book)
    {
        $book->load([
            'genres',
            'reviews.user',
        ]);

        $book->reviews->loadCount('likes');

        $book->loadAvg('reviews', 'rating');
        $book->loadCount('reviews');

        return (new BookResource($book))->additional([
            'reviews' => ReviewResource::collection($book->reviews),
        ]);
    }

    /**
     * 書籍を新規登録する
     */
    public function store(StoreBookApiRequest $request)
    {
        $validated = $request->validated();
        $genreIds = $validated['genres'];

        $book = new Book();
        $book->user_id = $validated['user_id'];
        $book->title = $validated['title'];
        $book->author = $validated['author'];
        $book->isbn = $validated['isbn'];
        $book->published_date = $validated['published_date'];
        $book->description = $validated['description'] ?? null;
        $book->image_url = $validated['image_url'] ?? null;
        $book->save();

        $book->genres()->sync($genreIds);

        $book->load('genres')
            ->loadAvg('reviews', 'rating')
            ->loadCount('reviews');

        return (new BookResource($book))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 書籍情報を更新する
     */
    public function update(UpdateBookApiRequest $request, Book $book)
    {
        $validated = $request->validated();
        $genreIds = $validated['genres'];

        $book->user_id = $validated['user_id'];
        $book->title = $validated['title'];
        $book->author = $validated['author'];
        $book->isbn = $validated['isbn'];
        $book->published_date = $validated['published_date'];
        $book->description = $validated['description'] ?? null;
        $book->image_url = $validated['image_url'] ?? null;
        $book->save();

        $book->genres()->sync($genreIds);

        $book->load('genres')
            ->loadAvg('reviews', 'rating')
            ->loadCount('reviews');

        return new BookResource($book);
    }

    /**
     * 書籍を削除する
     */
    public function destroy(Book $book)
    {
        DB::transaction(function () use ($book) {
            $reviews = $book->reviews;

            foreach ($reviews as $review) {
                $review->likes()->delete();
            }

            $book->reviews()->delete();
            $book->favorites()->delete();
            $book->genres()->detach();
            $book->delete();
        });

        return response()->noContent();
    }
}