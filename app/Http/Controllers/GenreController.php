<?php

namespace App\Http\Controllers;

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
        //
    }

    /**
     * ジャンルを登録する
     */
    public function store()
    {
        //
    }

    /**
     * ジャンル詳細を表示する
     */
    public function show(Genre $genre)
    {
        //
    }

    /**
     * ジャンル編集画面を表示する
     */
    public function edit(Genre $genre)
    {
        //
    }

    /**
     * ジャンル情報を更新する
     */
    public function update(Genre $genre)
    {
        //
    }

    /**
     * ジャンルを削除する
     */
    public function destroy(Genre $genre)
    {
        //
    }
}