<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    /**
     * 書籍一覧を表示する
     */
    public function index()
    {
        $books = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->latest()
            ->paginate(10);

        return view('books.index', compact('books'));
    }

    /**
     * 書籍登録画面を表示する
     */
    public function create()
    {
        $this->authorize('create', Book::class);

        $genres = Genre::orderBy('name')->get();

        return view('books.create', compact('genres'));
    }

    /**
     * 書籍を登録する
     */
    public function store(StoreBookRequest $request)
    {
        // 検証済みの入力値を取得する
        $validated = $request->validated();

        // ログインユーザーを登録者として書籍を作成する
        $book = $request->user()->books()->create([
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'],
            'published_date' => $validated['published_date'],
            'description' => $validated['description'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
        ]);

        // 選択されたジャンルを関連付ける
        $book->genres()->sync($validated['genres']);

        // 登録した書籍の詳細画面へ移動する
        return redirect()->route('books.show', $book)
            ->with('success', '書籍を登録しました');
    }

    /**
     * 書籍詳細を表示する
     */
    public function show(Book $book)
    {
        // 書籍のジャンルとレビュー情報を取得する
        $book->load([
            'genres',
            'reviews.user',
            'reviews.likedByUsers',
        ]);

        // 書籍詳細画面を表示する
        return view('books.show', compact('book'));
    }

    /**
     * 書籍編集画面を表示する
     */
    public function edit(Book $book)
    {
        // 書籍を編集する権限を確認する
        $this->authorize('update', $book);

        $genres = Genre::orderBy('name')->get();
        $bookGenreIds = $book->genres->pluck('id')->toArray();

        return view('books.edit', compact('book', 'genres', 'bookGenreIds'));
    }

    /**
     * 書籍情報を更新する
     */
    public function update(UpdateBookRequest $request, Book $book)
    {
        // 検証済みの入力値を取得する
        $validated = $request->validated();

        // 書籍情報を更新する
        $book->update([
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'],
            'published_date' => $validated['published_date'] ?? null,
            'description' => $validated['description'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
        ]);

        // 選択されたジャンルを関連付ける
        $book->genres()->sync($validated['genres'] ?? []);

        // 書籍詳細画面へ移動する
        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍情報を更新しました。');
    }

    /**
     * 書籍を削除する
     */
    public function destroy(Book $book)
    {
        // 関連データと書籍本体の削除をまとめて処理する
        DB::transaction(function () use ($book) {
            // 書籍に関連するレビューを取得する
            $reviews = $book->reviews;

            // レビューに関連するいいねを削除する
            foreach ($reviews as $review) {
                $review->likes()->delete();
            }

            // 書籍に関連するレビューを削除する
            $book->reviews()->delete();

            // 書籍に関連するお気に入りを削除する
            $book->favorites()->delete();

            // 書籍に関連するジャンルの関連レコードを削除する
            $book->genres()->detach();

            // 書籍を削除する
            $book->delete();
        });

        // 書籍一覧画面へ戻る
        return redirect()
            ->route('books.index')
            ->with('success', '書籍を削除しました。');
    }
}
