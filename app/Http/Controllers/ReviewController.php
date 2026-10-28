<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use App\Models\Review;

class ReviewController extends Controller
{
    /**
     * レビューを登録する
     */
    public function store(Request $request, Book $book)
    {
        // 入力値を検証する
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:1', 'max:1000'],
        ]);

        // 同じ書籍にレビューを投稿済みか確認する
        $alreadyReviewed = $book->reviews()
            ->where('user_id', $request->user()->id)
            ->exists();

        // 投稿済みの場合は書籍詳細画面へ戻る
        if ($alreadyReviewed) {
            return redirect()
                ->route('books.show', $book)
                ->withErrors(['review' => 'この書籍にはレビューを投稿済みです。']);
        }

        // ログインユーザーのレビューを登録する
        $book->reviews()->create([
            'user_id' => $request->user()->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
        ]);

        // 書籍詳細画面へ戻る
        return redirect()
            ->route('books.show', $book)
            ->with('success', 'レビューを投稿しました。');
    }
    /**
    * レビュー編集画面を表示する
    */
    public function edit(Review $review)
    {
    // レビューを編集する権限を確認する
    $this->authorize('update', $review);

    // レビュー編集画面を表示する
    return view('reviews.edit', compact('review'));
    }

     /**
     * レビューを更新する
     */
    public function update(Request $request, Review $review)
    {
        // レビューを更新する権限を確認する
        $this->authorize('update', $review);

        // 入力値を検証する
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:1', 'max:1000'],
        ]);

        // レビューを更新する
        $review->update([
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
        ]);

        // 書籍詳細画面へ移動する
        return redirect()
            ->route('books.show', $review->book_id)
            ->with('success', 'レビューを更新しました。');
    }

        /**
     * レビューを削除する
     */
    public function destroy(Review $review)
    {
        // レビューを削除する権限を確認する
        $this->authorize('delete', $review);


        // レビューに関連するいいねを削除する
        $review->likes()->delete();

        // レビューを削除する
        $bookId = $review->book_id;
        $review->delete();

        // 書籍詳細画面へ移動する
        return redirect()
            ->route('books.show', $bookId)
            ->with('success', 'レビューを削除しました。');
    }
}