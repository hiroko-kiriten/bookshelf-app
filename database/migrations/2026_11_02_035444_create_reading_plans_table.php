<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reading_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('book_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->date('target_date');
            $table->timestamp('completed_at')->nullable();
            $table->string('status')->default('in_progress');
            $table->timestamps();

            // 同じユーザーが同じ書籍の計画を重複登録できないようにする
            $table->unique(['user_id', 'book_id']);

            // 期限に基づくバッチ処理で使用する
            $table->index(['status', 'target_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_plans');
    }
};
