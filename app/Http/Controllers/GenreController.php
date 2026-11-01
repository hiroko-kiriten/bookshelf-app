<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGenreRequest;
use App\Http\Requests\UpdateGenreRequest;
use App\Models\Genre;

class GenreController extends Controller
{
    /**
     * ジャンル一覧を表示する
     */
    public function index()
    {
        $genres = Genre::withCount('books')->get();

        return view('genres.index', compact('genres'));
    }

    /**
     * ジャンル登録画面を表示する
     */
    public function create()
    {
        // ジャンル登録画面を表示する
        return view('genres.create');
    }

    /**
     * ジャンルを登録する
     */
    public function store(StoreGenreRequest $request)
    {
        // 検証済みの入力値を取得する
        $validated = $request->validated();

        // ジャンルを登録する
        Genre::create($validated);

        // ジャンル一覧画面へ戻る
        return redirect()
            ->route('genres.index')
            ->with('success', 'ジャンルを登録しました。');
    }

    /**
     * ジャンル詳細を表示する
     */
    public function show(Genre $genre)
    {
        // ジャンルに属する書籍を取得する
        $books = $genre->books()
            ->with('genres')
            ->oldest('books.created_at')
            ->paginate(10);

        // ジャンル詳細画面を表示する
        return view('genres.show', compact('genre', 'books'));
    }

    /**
     * ジャンル編集画面を表示する
     */
    public function edit(Genre $genre)
    {
        // ジャンル編集画面を表示する
        return view('genres.edit', compact('genre'));
    }

    /**
     * ジャンル情報を更新する
     */
    public function update(UpdateGenreRequest $request, Genre $genre)
    {
        // 検証済みの入力値を取得する
        $validated = $request->validated();

        // ジャンル情報を更新する
        $genre->update($validated);

        // ジャンル一覧画面へ戻る
        return redirect()
            ->route('genres.index')
            ->with('success', 'ジャンルを更新しました。');
    }

    /**
     * ジャンルを削除する
     */
    public function destroy(Genre $genre)
    {
        // 書籍に紐づいているジャンルは削除しない
        if ($genre->books()->exists()) {
            abort(403, '書籍に紐づいているジャンルは削除できません。');
        }

        // ジャンルを削除する
        $genre->delete();

        // ジャンル一覧画面へ戻る
        return redirect()
            ->route('genres.index')
            ->with('success', 'ジャンルを削除しました。');
    }
}
