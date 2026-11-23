<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendReadingPlanNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-reading-plan-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '読書計画の通知送信と期限切れ処理を実行する';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = Carbon::today();

        // 目標日を過ぎた進行中の読書計画を期限切れにする
        ReadingPlan::where('status', ReadingPlanStatus::IN_PROGRESS->value)
            ->whereDate('target_date', '<', $today)
            ->update([
                'status' => ReadingPlanStatus::EXPIRED->value,
                'updated_at' => now(),
            ]);

        // 読了予定日の3日前に通知する
        $this->sendNotifications(
            $today->copy()->addDays(3),
            ReadingPlanStatus::IN_PROGRESS,
            'three_days_before'
        );

        // 読了予定日当日に通知する
        $this->sendNotifications(
            $today,
            ReadingPlanStatus::IN_PROGRESS,
            'on_due_date'
        );

        // 読了予定日の3日後に通知する
        $this->sendNotifications(
            $today->copy()->subDays(3),
            ReadingPlanStatus::EXPIRED,
            'three_days_after'
        );

        $this->info('読書計画の通知処理が完了しました。');

        return self::SUCCESS;
    }

    /**
     * 指定した条件に該当する読書計画へ通知する。
     */
    private function sendNotifications(
        Carbon $targetDate,
        ReadingPlanStatus $status,
        string $timing
    ): void {
        $readingPlans = ReadingPlan::with(['user', 'book'])
            ->whereDate('target_date', $targetDate)
            ->where('status', $status->value)
            ->get();

        foreach ($readingPlans as $readingPlan) {
            $date = $readingPlan->target_date->toDateString();

            // 同じ通知が既に送信されていないか確認する
            $alreadySent = $readingPlan->user->notifications()
                ->where('type', ReadingPlanNotification::class)
                ->where('data->reading_plan_id', $readingPlan->id)
                ->where('data->timing', $timing)
                ->where('data->target_date', $date)
                ->exists();

            if ($alreadySent) {
                continue;
            }

            [$title, $body] = $this->getNotificationContent(
                $readingPlan->book->title,
                $timing
            );

            // 読書計画を登録したユーザーに通知する
            $readingPlan->user->notify(
                new ReadingPlanNotification(
                    $title,
                    $body,
                    $readingPlan->id,
                    $timing,
                    $date
                )
            );
        }
    }

    /**
     * 通知のタイトルと本文を取得する。
     */
    private function getNotificationContent(
        string $bookTitle,
        string $timing
    ): array {
        return match ($timing) {
            'three_days_before' => [
                '読書計画の期限が近づいています',
                "『{$bookTitle}』の読了予定日まで、あと3日です。計画的に読書を進めましょう。",
            ],
            'on_due_date' => [
                '読書計画の期限日です',
                "本日は『{$bookTitle}』の読了予定日です。本日24時までに読了した場合は、読書計画一覧から「読了する」を選択してください。",
            ],
            'three_days_after' => [
                '読書計画の期限が過ぎています',
                "『{$bookTitle}』の読了予定日から3日が経過しました。読書計画の状況を確認してみましょう。",
            ],
        };
    }
}
