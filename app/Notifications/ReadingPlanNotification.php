<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReadingPlanNotification extends Notification
{
    use Queueable;

    /**
     * 通知に必要な情報を設定する。
     */
    public function __construct(
        public string $title,
        public string $body,
        public int $readingPlanId,
        public string $timing,
        public string $targetDate
    ) {}

    /**
     * 通知の送信先を設定する。
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * データベースに保存する通知内容を設定する。
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'reading_plan_id' => $this->readingPlanId,
            'timing' => $this->timing,
            'target_date' => $this->targetDate,
        ];
    }
}
