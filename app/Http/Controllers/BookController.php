<?php

namespace App\Http\Controllers;

use App\Models\Book;

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
            ->paginate(12);

        return view('books.index', compact('books'));
    }

    /**
     * 書籍登録画面を表示する
     */
    public function create()
    {
        //
    }

    /**
     * 書籍を登録する
     */
    public function store()
    {
        //
    }

    /**
     * 書籍詳細を表示する
     */
    public function show(Book $book)
    {
        //
    }

    /**
     * 書籍編集画面を表示する
     */
    public function edit(Book $book)
    {
        //
    }

    /**
     * 書籍情報を更新する
     */
    public function update(Book $book)
    {
        //
    }

    /**
     * 書籍を削除する
     */
    public function destroy(Book $book)
    {
        //
    }
}