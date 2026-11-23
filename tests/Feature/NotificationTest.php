<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ReadingPlanNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    // 自分の通知一覧を表示できること
    public function test_user_can_view_own_notifications(): void
    {
        $user = User::factory()->create();

        $user->notify(new ReadingPlanNotification(
            '通知タイトル',
            '通知本文',
            1,
            'three_days_before',
            today()->addDays(3)->toDateString()
        ));

        $response = $this->actingAs($user)
            ->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('通知タイトル');
        $response->assertSee('通知本文');
    }

    // 他人の通知は表示されないこと
    public function test_user_cannot_view_another_users_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherUser->notify(new ReadingPlanNotification(
            '他人の通知',
            '他人の通知本文',
            1,
            'three_days_before',
            today()->addDays(3)->toDateString()
        ));

        $response = $this->actingAs($user)
            ->get(route('notifications.index'));

        $response->assertOk();
        $response->assertDontSee('他人の通知');
    }

    // 通知一覧を表示しただけでは既読にならないこと
    public function test_viewing_notifications_does_not_mark_them_as_read(): void
    {
        $user = User::factory()->create();

        $user->notify(new ReadingPlanNotification(
            '未読通知',
            '通知本文',
            1,
            'three_days_before',
            today()->addDays(3)->toDateString()
        ));

        $notification = $user->notifications()->first();

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk();

        $this->assertNull($notification->fresh()->read_at);
    }

    // 自分の通知を既読にできること
    public function test_user_can_mark_own_notification_as_read(): void
    {
        $user = User::factory()->create();

        $user->notify(new ReadingPlanNotification(
            '通知タイトル',
            '通知本文',
            1,
            'three_days_before',
            today()->addDays(3)->toDateString()
        ));

        $notification = $user->notifications()->first();

        $response = $this->actingAs($user)
            ->post(route('notifications.read', $notification->id));

        $response->assertRedirect(route('notifications.index'));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    // 他人の通知を既読にできないこと
    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherUser->notify(new ReadingPlanNotification(
            '他人の通知',
            '通知本文',
            1,
            'three_days_before',
            today()->addDays(3)->toDateString()
        ));

        $notification = $otherUser->notifications()->first();

        $response = $this->actingAs($user)
            ->post(route('notifications.read', $notification->id));

        $response->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    // 未ログインユーザーは通知一覧を表示できないこと
    public function test_guest_cannot_view_notifications(): void
    {
        $response = $this->get(route('notifications.index'));

        $response->assertRedirect('/login');
    }
}
