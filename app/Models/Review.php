<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'book_id',
        'rating',
        'comment',
    ];

    // レビューを投稿したユーザー
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // レビュー対象の書籍
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    // レビューに付けられたいいね
    public function likes(): HasMany
    {
        return $this->hasMany(ReviewLike::class);
    }
}