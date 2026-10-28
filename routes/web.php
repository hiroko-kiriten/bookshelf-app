<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReviewLikeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// 書籍一覧は誰でも閲覧できる
Route::get('/books', [BookController::class, 'index'])
    ->name('books.index');

// 書籍登録はログインユーザーのみ
Route::middleware('auth')->group(function () {
    Route::get('/books/create', [BookController::class, 'create'])
        ->name('books.create');

    Route::post('/books', [BookController::class, 'store'])
        ->name('books.store');
});

// 書籍詳細は誰でも閲覧できる
Route::get('/books/{book}', [BookController::class, 'show'])
    ->name('books.show');

// 書籍編集・更新は登録者本人のみ
Route::middleware(['auth', 'can:update,book'])->group(function () {
    Route::get('/books/{book}/edit', [BookController::class, 'edit'])
        ->name('books.edit');

    Route::put('/books/{book}', [BookController::class, 'update'])
        ->name('books.update');

});

// 書籍削除は登録者本人のみ
Route::delete('/books/{book}', [BookController::class, 'destroy'])
    ->middleware(['auth', 'can:delete,book'])
    ->name('books.destroy');

// ジャンル関連のルート
Route::resource('genres', GenreController::class)
    ->middleware('auth');

// 評価ランキングのルート
Route::get('/ranking', [RankingController::class, 'index'])
    ->name('ranking.index');

// お気に入り関連のルート
Route::middleware('auth')->group(function () {
    Route::get('/favorites', [FavoriteController::class, 'index'])
        ->name('favorites.index');

    Route::post('/favorites/{book}/toggle', [FavoriteController::class, 'toggle'])
        ->name('favorites.toggle');
});

// レビュー投稿はログインユーザーのみ
Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])
    ->middleware('auth')
    ->name('reviews.store');

// レビュー編集画面は投稿者本人のみ表示できる
Route::get('/reviews/{review}/edit', [ReviewController::class, 'edit'])
    ->middleware(['auth', 'can:update,review'])
    ->name('reviews.edit');

// レビュー更新は投稿者本人のみ可能
Route::put('/reviews/{review}', [ReviewController::class, 'update'])
    ->middleware(['auth', 'can:update,review'])
    ->name('reviews.update');

// レビュー削除は投稿者本人のみ可能
Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])
    ->middleware(['auth', 'can:delete,review'])
    ->name('reviews.destroy');

// レビューのいいねはログインユーザーのみ可能
Route::post('/reviews/{review}/like', [ReviewLikeController::class, 'toggle'])
    ->middleware('auth')
    ->name('reviews.like');