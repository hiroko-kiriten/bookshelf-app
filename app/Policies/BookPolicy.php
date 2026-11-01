<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    /**
     * 書籍一覧の閲覧を許可する
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * 書籍詳細の閲覧を許可する
     */
    public function view(User $user, Book $book): bool
    {
        return true;
    }

    /**
     * 書籍の登録を許可する
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * 登録した本人だけに編集を許可する
     */
    public function update(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }

    /**
     * 登録した本人だけに削除を許可する
     */
    public function delete(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }

    /**
     * 書籍の復元を許可しない
     */
    public function restore(User $user, Book $book): bool
    {
        return false;
    }

    /**
     * 書籍の完全削除を許可しない
     */
    public function forceDelete(User $user, Book $book): bool
    {
        return false;
    }
}
