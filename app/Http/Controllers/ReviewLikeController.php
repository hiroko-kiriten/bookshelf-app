<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\RedirectResponse;

class ReviewLikeController extends Controller
{
    /**
     * レビューのいいねを切り替える
     */
    public function toggle(Review $review): RedirectResponse
    {
        // ログインユーザーのいいねを切り替える
        $review->likedByUsers()->toggle(auth()->id());

        // 書籍詳細画面へ戻る
        return redirect()
            ->route('books.show', $review->book_id);
    }
}
