<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    public function run(): void
    {
        // いいねを付けるユーザー
        $users = User::all();

        // 登録済みのレビューを取得
        $reviews = Review::with('user')->get();

        foreach ($reviews as $review) {
            // レビュー投稿者本人を除外
            $eligibleUsers = $users
                ->where('id', '!=', $review->user_id)
                ->values();

            // 0〜3人をランダムに選ぶ
            $likeCount = random_int(
                0,
                min(3, $eligibleUsers->count())
            );

            $likingUsers = $eligibleUsers
                ->shuffle()
                ->take($likeCount);

            foreach ($likingUsers as $user) {
                // 同じユーザーによる重複いいねを防ぐ
                ReviewLike::firstOrCreate([
                    'user_id' => $user->id,
                    'review_id' => $review->id,
                ]);
            }
        }
    }
}