<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // ユーザーが登録した書籍
    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    // ユーザーが投稿したレビュー
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    // ユーザーがお気に入り登録した書籍
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    // ユーザーが付けたレビューへのいいね
    public function reviewLikes(): HasMany
    {
        return $this->hasMany(ReviewLike::class);
    }

    // ユーザーがお気に入り登録した書籍
    public function favoriteBooks(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'favorites');
    }

    // ユーザーがいいねしたレビュー
    public function likedReviews(): BelongsToMany
    {
        return $this->belongsToMany(Review::class, 'review_likes');
    }
}