<?php

use App\Http\Controllers\Api\BookApiController;
use App\Http\Controllers\Api\ReviewApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// 公開API
Route::prefix('v1')->group(function () {
    Route::get('/books', [BookApiController::class, 'index']);
    Route::get('/books/{book}', [BookApiController::class, 'show']);
    Route::post('/books', [BookApiController::class, 'store']);
    Route::put('/books/{book}', [BookApiController::class, 'update']);
    Route::delete('/books/{book}', [BookApiController::class, 'destroy']);
    Route::get('/books/{book}/reviews', [ReviewApiController::class, 'index']);
});

// ログインユーザー取得（既存ルート）
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});