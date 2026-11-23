<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanTest extends TestCase
{
    use RefreshDatabase;

    // 自分の読書計画一覧を表示できること
    public function test_user_can_view_own_reading_plans(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownBook = Book::factory()->create(['title' => '自分の書籍']);
        $otherBook = Book::factory()->create(['title' => '他人の書籍']);

        $user->readingPlans()->create([
            'book_id' => $ownBook->id,
            'target_date' => today()->addDays(7),
            'status' => ReadingPlanStatus::IN_PROGRESS,
        ]);

        $otherUser->readingPlans()->create([
            'book_id' => $otherBook->id,
            'target_date' => today()->addDays(7),
            'status' => ReadingPlanStatus::IN_PROGRESS,
        ]);

        $response = $this->actingAs($user)->get(route('reading-plans.index'));

        $response->assertOk();
        $response->assertSee('自分の書籍');
        $response->assertDontSee('他人の書籍');
    }

    // 読書計画を登録できること
    public function test_user_can_create_reading_plan(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(
            route('reading-plans.store'),
            [
                'book_id' => $book->id,
                'target_date' => today()->addDays(7)->toDateString(),
            ]
        );

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::IN_PROGRESS->value,
        ]);
    }

    // 同じ書籍を重複登録できないこと
    public function test_user_cannot_create_duplicate_reading_plan(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $user->readingPlans()->create([
            'book_id' => $book->id,
            'target_date' => today()->addDays(7),
            'status' => ReadingPlanStatus::IN_PROGRESS,
        ]);

        $response = $this->actingAs($user)->from(route('reading-plans.create'))
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => today()->addDays(10)->toDateString(),
            ]);

        $response->assertSessionHasErrors('book_id');

        $this->assertDatabaseCount('reading_plans', 1);
    }

    // 読了予定日を更新できること
    public function test_user_can_update_reading_plan(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $plan = $user->readingPlans()->create([
            'book_id' => $book->id,
            'target_date' => today()->addDays(7),
            'status' => ReadingPlanStatus::IN_PROGRESS,
        ]);

        $newDate = today()->addDays(14)->toDateString();

        $response = $this->actingAs($user)->put(
            route('reading-plans.update', $plan),
            ['target_date' => $newDate]
        );

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => $newDate,
            'status' => ReadingPlanStatus::IN_PROGRESS->value,
        ]);
    }

    // 読了処理ができること
    public function test_user_can_complete_reading_plan(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $plan = $user->readingPlans()->create([
            'book_id' => $book->id,
            'target_date' => today()->addDays(7),
            'status' => ReadingPlanStatus::IN_PROGRESS,
        ]);

        $response = $this->actingAs($user)->post(
            route('reading-plans.complete', $plan)
        );

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'status' => ReadingPlanStatus::COMPLETED->value,
        ]);

        $this->assertNotNull($plan->fresh()->completed_at);
    }

    // 読書計画を削除できること
    public function test_user_can_delete_reading_plan(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $plan = $user->readingPlans()->create([
            'book_id' => $book->id,
            'target_date' => today()->addDays(7),
            'status' => ReadingPlanStatus::IN_PROGRESS,
        ]);

        $response = $this->actingAs($user)->delete(
            route('reading-plans.destroy', $plan)
        );

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $plan->id,
        ]);
    }

    // 他人の読書計画を操作できないこと
    public function test_user_cannot_access_another_users_reading_plan(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $plan = $otherUser->readingPlans()->create([
            'book_id' => $book->id,
            'target_date' => today()->addDays(7),
            'status' => ReadingPlanStatus::IN_PROGRESS,
        ]);

        $response = $this->actingAs($user)->get(
            route('reading-plans.edit', $plan)
        );

        $response->assertForbidden();

        $response = $this->put(
            route('reading-plans.update', $plan),
            ['target_date' => today()->addDays(14)->toDateString()]
        );

        $response->assertForbidden();

        $response = $this->post(
            route('reading-plans.complete', $plan)
        );

        $response->assertForbidden();

        $response = $this->delete(
            route('reading-plans.destroy', $plan)
        );

        $response->assertForbidden();
    }

    // 読了済みの計画は編集できないこと
    public function test_completed_reading_plan_cannot_be_edited(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $plan = $user->readingPlans()->create([
            'book_id' => $book->id,
            'target_date' => today(),
            'completed_at' => now(),
            'status' => ReadingPlanStatus::COMPLETED,
        ]);

        $response = $this->actingAs($user)->get(
            route('reading-plans.edit', $plan)
        );

        $response->assertForbidden();
    }

    // 未ログインユーザーは読書計画を表示できないこと
    public function test_guest_cannot_view_reading_plans(): void
    {
        $response = $this->get(route('reading-plans.index'));

        $response->assertRedirect('/login');
    }
}
