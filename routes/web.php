<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\RankingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GenreController;

Route::get('/', function () {
    return view('welcome');
});

// 書籍関連のルート
Route::resource('books', BookController::class);

// ジャンル関連のルート
Route::resource('genres', GenreController::class);

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