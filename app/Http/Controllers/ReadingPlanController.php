<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    // 読書計画一覧
    public function index(Request $request): View
    {
        $currentStatus = $request->query('status', '');

        // 不正なステータスは絞り込みなしとして扱う
        if (! ReadingPlanStatus::tryFrom($currentStatus)) {
            $currentStatus = '';
        }

        $readingPlans = $request->user()
            ->readingPlans()
            ->with('book')
            ->when(
                $currentStatus !== '',
                fn ($query) => $query->where('status', $currentStatus)
            )
            ->orderBy('target_date')
            ->get();

        return view('reading-plans.index', compact(
            'readingPlans',
            'currentStatus'
        ));
    }

    // 読書計画登録画面
    public function create(): View
    {
        $books = Book::orderBy('title')->get();

        return view('reading-plans.create', compact('books'));
    }

    // 読書計画登録
    public function store(StoreReadingPlanRequest $request): RedirectResponse
    {
        $request->user()->readingPlans()->create([
            'book_id' => $request->validated('book_id'),
            'target_date' => $request->validated('target_date'),
            'status' => ReadingPlanStatus::IN_PROGRESS,
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を登録しました。');
    }

    // 読書計画編集画面
    public function edit(ReadingPlan $readingPlan): View
    {
        $this->authorizeOwner($readingPlan);

        // 読了済みの計画は編集できない
        abort_if(
            $readingPlan->status === ReadingPlanStatus::COMPLETED,
            403
        );

        $readingPlan->load('book');

        return view('reading-plans.edit', compact('readingPlan'));
    }

    // 読書計画更新
    public function update(
        UpdateReadingPlanRequest $request,
        ReadingPlan $readingPlan
    ): RedirectResponse {
        $this->authorizeOwner($readingPlan);

        // 読了済みの計画は編集できない
        abort_if(
            $readingPlan->status === ReadingPlanStatus::COMPLETED,
            403
        );

        $targetDate = $request->validated('target_date');

        $readingPlan->update([
            'target_date' => $targetDate,
            'status' => $targetDate < today()
                ? ReadingPlanStatus::EXPIRED
                : ReadingPlanStatus::IN_PROGRESS,
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を更新しました。');
    }

    // 読了処理
    public function complete(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorizeOwner($readingPlan);

        abort_if(
            $readingPlan->status === ReadingPlanStatus::COMPLETED,
            403
        );

        $readingPlan->update([
            'status' => ReadingPlanStatus::COMPLETED,
            'completed_at' => now(),
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を読了にしました。');
    }

    // 読書計画削除
    public function destroy(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorizeOwner($readingPlan);

        $readingPlan->delete();

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を削除しました。');
    }

    // ログインユーザー本人の計画か確認
    private function authorizeOwner(ReadingPlan $readingPlan): void
    {
        abort_unless(
            $readingPlan->user_id === auth()->id(),
            403
        );
    }
}
