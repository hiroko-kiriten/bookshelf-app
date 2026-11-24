<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * 外部キー制約をカスケード削除に変更する。
     */
    public function up(): void
    {
        // books
        Schema::table('books', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        // book_genre
        Schema::table('book_genre', function (Blueprint $table) {
            $table->dropForeign(['book_id']);
            $table->dropForeign(['genre_id']);

            $table->foreign('book_id')
                ->references('id')
                ->on('books')
                ->cascadeOnDelete();

            $table->foreign('genre_id')
                ->references('id')
                ->on('genres')
                ->cascadeOnDelete();
        });

        // favorites
        Schema::table('favorites', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['book_id']);

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('book_id')
                ->references('id')
                ->on('books')
                ->cascadeOnDelete();
        });

        // reviews
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['book_id']);

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('book_id')
                ->references('id')
                ->on('books')
                ->cascadeOnDelete();
        });

        // review_likes
        Schema::table('review_likes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['review_id']);

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('review_id')
                ->references('id')
                ->on('reviews')
                ->cascadeOnDelete();
        });
    }

    /**
     * 外部キー制約を元の状態に戻す。
     */
    public function down(): void
    {
        // books
        Schema::table('books', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')
                ->references('id')
                ->on('users');
        });

        // book_genre
        Schema::table('book_genre', function (Blueprint $table) {
            $table->dropForeign(['book_id']);
            $table->dropForeign(['genre_id']);

            $table->foreign('book_id')
                ->references('id')
                ->on('books');

            $table->foreign('genre_id')
                ->references('id')
                ->on('genres');
        });

        // favorites
        Schema::table('favorites', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['book_id']);

            $table->foreign('user_id')
                ->references('id')
                ->on('users');

            $table->foreign('book_id')
                ->references('id')
                ->on('books');
        });

        // reviews
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['book_id']);

            $table->foreign('user_id')
                ->references('id')
                ->on('users');

            $table->foreign('book_id')
                ->references('id')
                ->on('books');
        });

        // review_likes
        Schema::table('review_likes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['review_id']);

            $table->foreign('user_id')
                ->references('id')
                ->on('users');

            $table->foreign('review_id')
                ->references('id')
                ->on('reviews');
        });
    }
};
