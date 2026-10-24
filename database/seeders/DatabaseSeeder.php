<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ユーザーの初期データ
        $this->call(UserSeeder::class);

        // ジャンルの初期データ
        $this->call(GenreSeeder::class);

        // 書籍の初期データ
        $this->call(BookSeeder::class);

        // レビューの初期データ
        $this->call(ReviewSeeder::class);

        // お気に入りの初期データ
        $this->call(FavoriteSeeder::class);

        // レビューへのいいねの初期データ
        $this->call(ReviewLikeSeeder::class);
    }
}