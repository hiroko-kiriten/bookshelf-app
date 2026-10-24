<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'author',
        'isbn',
        'published_date',
        'description',
        'image_url',
    ];

    // 書籍を登録したユーザー
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // 書籍に関連付けられたジャンル
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'book_genre');
    }

    // 書籍に投稿されたレビュー
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    // 書籍のお気に入り登録
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }
}