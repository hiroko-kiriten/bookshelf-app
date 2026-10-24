<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    public function run(): void
    {
        // ユーザーごとのお気に入り書籍（ISBN）
        $favorites = [
            'yamada@example.com' => [
                '9784101010014',
                '9784873115658',
                '9784478025819',
            ],
            'suzuki@example.com' => [
                '9784422100524',
                '9784863940246',
                '9784309226712',
                '9784163902302',
            ],
            'tanaka@example.com' => [
                '9784873115658',
                '9784101010021',
                '9784048930598',
                '9784822289607',
                '9784822251468',
            ],
            'sato@example.com' => [
                '9784422100524',
                '9784863940246',
                '9784163902302',
            ],
            'takahashi@example.com' => [
                '9784101010014',
                '9784309226712',
                '9784478025819',
                '9784048930598',
            ],
        ];

        foreach ($favorites as $email => $isbns) {
            // お気に入り登録するユーザーを取得
            $user = User::where('email', $email)->firstOrFail();

            foreach ($isbns as $isbn) {
                // お気に入り登録する書籍を取得
                $book = Book::where('isbn', $isbn)->firstOrFail();

                // お気に入りを重複させずに登録
                Favorite::firstOrCreate([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                ]);
            }
        }
    }
}