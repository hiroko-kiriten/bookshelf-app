<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
/**
 * お気に入り一覧を表示する
 */
public function index(Request $request): View
{
    $books = $request->user()
        ->favoriteBooks()
        ->paginate(10);

    return view('favorites.index', compact('books'));
}

    /**
     * お気に入り登録・解除を切り替える
     */
    public function toggle(Request $request, Book $book): RedirectResponse
    {
        $favorite = $request->user()
            ->favorites()
            ->where('book_id', $book->id)
            ->first();

        if ($favorite) {
            $favorite->delete();
        } else {
            $request->user()->favorites()->create([
                'book_id' => $book->id,
            ]);
        }

        return back();
    }
}
