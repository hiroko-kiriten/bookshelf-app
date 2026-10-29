<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Book;

class ReviewApiController extends Controller
{
    // 書籍のレビュー一覧を取得する
    public function index(Book $book)
    {
        $reviews = $book->reviews()
            ->with('user')
            ->withCount('likes')
            ->latest()
            ->get();

        return ReviewResource::collection($reviews);
    }
}