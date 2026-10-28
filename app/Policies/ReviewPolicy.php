<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * レビューの更新を許可するユーザーを判定する
     */
    public function update(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }

    /**
     * レビューの削除を許可するユーザーを判定する
     */
    public function delete(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }
}