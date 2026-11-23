<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanNotificationTest extends TestCase
{
    use RefreshDatabase;

    // 読了予定日の3日前に通知されること
    public function test_notification_is_sent_three_days_before_target_date(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['title' => 'テスト書籍']);

        $this->createReadingPlan(
            $user,
            $book,
            today()->addDays(3),
            ReadingPlanStatus::IN_PROGRESS
        );

        $this->artisan('app:send-reading-plan-notifications')
            ->assertExitCode(0);

        $notification = $user->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame(ReadingPlanNotification::class, $notification->type);
        $this->assertSame(
            '読書計画の期限が近づいています',
            $notification->data['title']
        );
        $this->assertSame(
            'three_days_before',
            $notification->data['timing']
        );
        $this->assertSame(
            'テスト書籍',
            $book->title
        );
    }

    // 読了予定日当日に通知されること
    public function test_notification_is_sent_on_target_date(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['title' => 'テスト書籍']);

        $this->createReadingPlan(
            $user,
            $book,
            today(),
            ReadingPlanStatus::IN_PROGRESS
        );

        $this->artisan('app:send-reading-plan-notifications')
            ->assertExitCode(0);

        $notification = $user->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame(
            '読書計画の期限日です',
            $notification->data['title']
        );
        $this->assertSame(
            'on_due_date',
            $notification->data['timing']
        );
    }

    // 目標日を過ぎた計画が期限切れになること
    public function test_overdue_reading_plan_becomes_expired(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $plan = $this->createReadingPlan(
            $user,
            $book,
            today()->subDay(),
            ReadingPlanStatus::IN_PROGRESS
        );

        $this->artisan('app:send-reading-plan-notifications')
            ->assertExitCode(0);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'status' => ReadingPlanStatus::EXPIRED->value,
        ]);
    }

    // 読了予定日の3日後に通知されること
    public function test_notification_is_sent_three_days_after_target_date(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['title' => 'テスト書籍']);

        $this->createReadingPlan(
            $user,
            $book,
            today()->subDays(3),
            ReadingPlanStatus::IN_PROGRESS
        );

        $this->artisan('app:send-reading-plan-notifications')
            ->assertExitCode(0);

        $notification = $user->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame(
            '読書計画の期限が過ぎています',
            $notification->data['title']
        );
        $this->assertSame(
            'three_days_after',
            $notification->data['timing']
        );
    }

    // 同じ通知が重複送信されないこと
    public function test_duplicate_notifications_are_not_sent(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->createReadingPlan(
            $user,
            $book,
            today()->addDays(3),
            ReadingPlanStatus::IN_PROGRESS
        );

        $this->artisan('app:send-reading-plan-notifications');
        $this->artisan('app:send-reading-plan-notifications');

        $this->assertDatabaseCount('notifications', 1);
    }

    // 通知は読書計画を登録したユーザーだけに送信されること
    public function test_notification_is_sent_only_to_plan_owner(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $this->createReadingPlan(
            $user,
            $book,
            today()->addDays(3),
            ReadingPlanStatus::IN_PROGRESS
        );

        $this->artisan('app:send-reading-plan-notifications')
            ->assertExitCode(0);

        $this->assertCount(1, $user->notifications);
        $this->assertCount(0, $otherUser->notifications);
    }

    /**
     * テスト用の読書計画を作成する。
     */
    private function createReadingPlan(
        User $user,
        Book $book,
        $targetDate,
        ReadingPlanStatus $status
    ): ReadingPlan {
        return $user->readingPlans()->create([
            'book_id' => $book->id,
            'target_date' => $targetDate,
            'status' => $status,
        ]);
    }
}
