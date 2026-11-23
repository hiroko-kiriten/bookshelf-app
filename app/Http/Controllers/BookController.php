<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class BookController extends Controller
{
    /**
     * 書籍一覧を表示する
     */
    public function index()
    {
        // 書籍一覧の基本クエリを作成する
        $query = Book::with('genres')
            ->withAvg('reviews', 'rating');

        // キーワードが入力されている場合はタイトル・著者を検索する
        if (request()->filled('keyword')) {
            $keyword = request('keyword');

            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('author', 'like', "%{$keyword}%");
            });
        }

        // ジャンルが選択されている場合は該当ジャンルに絞り込む
        if (request()->filled('genre')) {
            $genreId = request('genre');

            $query->whereHas('genres', function ($q) use ($genreId) {
                $q->where('genres.id', $genreId);
            });
        }

        // 選択されたソート条件を取得する
        $sort = request('sort', 'newest');

        // ソート条件に応じて書籍の並び順を変更する
        switch ($sort) {
            case 'oldest':
                // 登録日時が古い順に並べる
                $query->oldest();
                break;

            case 'rating':
                // レビュー件数も取得して評価順に並べる
                $query->withCount('reviews')
                    ->orderByDesc('reviews_avg_rating')
                    ->orderByDesc('reviews_count')
                    ->orderByDesc('created_at');
                break;

            case 'title':
                // タイトルの昇順に並べる
                $query->orderBy('title');
                break;

            default:
                // 指定がない場合は登録日時が新しい順に並べる
                $query->latest();
                break;
        }

        // 検索条件をページ移動後も引き継いで10件ずつ取得する
        $books = $query->paginate(10)->withQueryString();
        // ジャンル選択欄に表示するジャンルを取得する
        $genres = Genre::orderBy('name')->get();

        return view('books.index', compact('books', 'genres'));
    }

    /**
     * ISBNからGoogle Books APIで書籍情報を検索する
     */
    public function searchByIsbn($isbn)
    {

        // ISBNが13桁の数字でない場合はエラーを返す
        if (! preg_match('/^\d{13}$/', $isbn)) {
            return response()->json([
                'error' => 'ISBNは13桁の数字で入力してください。',
            ], 422);
        }

        try {
            // Google Books APIにISBNとAPIキーを指定して書籍情報を問い合わせる
            $response = Http::timeout(10)->get(
                'https://www.googleapis.com/books/v1/volumes',
                [
                    'q' => "isbn:{$isbn}",
                    'key' => config('services.google_books.key'),
                ]
            );
        } catch (ConnectionException $e) {
            // 通信タイムアウトや接続失敗の場合はエラーを返す
            return response()->json([
                'error' => '通信に失敗しました。時間をおいて再度お試しください。',
            ], 503);
        }

        // APIの利用制限に達した場合はエラーを返す
        if ($response->status() === 429) {
            return response()->json([
                'error' => '現在、書籍情報を取得できません。時間をおいて再度お試しください。',
            ], 429);
        }

        // API側でエラーが発生した場合はエラーを返す
        if (! $response->successful()) {
            return response()->json([
                'error' => '書籍情報の取得に失敗しました。時間をおいて再度お試しください。',
            ], 503);
        }

        // APIから取得した最初の書籍情報を取り出す
        $volume = $response->json('items.0.volumeInfo');

        // 該当する書籍が見つからない場合はエラーを返す
        if (! $volume) {
            return response()->json([
                'error' => '書籍情報が見つかりませんでした。',
            ], 404);
        }

        // Google Books APIの情報をフォーム用の形式に変換する
        return response()->json([
            'title' => $volume['title'] ?? null,
            'author' => isset($volume['authors'])
                ? implode('、', $volume['authors'])
                : null,
            'description' => $volume['description'] ?? null,
            'image_url' => $volume['imageLinks']['thumbnail'] ?? null,
            'published_date' => $volume['publishedDate'] ?? null,
        ]);
    }

    /**
     * 書籍登録画面を表示する
     */
    public function create()
    {
        $this->authorize('create', Book::class);

        $genres = Genre::orderBy('name')->get();

        return view('books.create', compact('genres'));
    }

    /**
     * 書籍を登録する
     */
    public function store(StoreBookRequest $request)
    {
        // 検証済みの入力値を取得する
        $validated = $request->validated();

        // ログインユーザーを登録者として書籍を作成する
        $book = $request->user()->books()->create([
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'],
            'published_date' => $validated['published_date'],
            'description' => $validated['description'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
        ]);

        // 選択されたジャンルを関連付ける
        $book->genres()->sync($validated['genres']);

        // 登録した書籍の詳細画面へ移動する
        return redirect()->route('books.show', $book)
            ->with('success', '書籍を登録しました');
    }

    /**
     * 書籍詳細を表示する
     */
    public function show(Book $book)
    {
        // 書籍のジャンルとレビュー情報を取得する
        $book->load([
            'genres',
            'reviews.user',
            'reviews.likedByUsers',
        ]);

        // 書籍詳細画面を表示する
        return view('books.show', compact('book'));
    }

    /**
     * 書籍編集画面を表示する
     */
    public function edit(Book $book)
    {
        // 書籍を編集する権限を確認する
        $this->authorize('update', $book);

        $genres = Genre::orderBy('name')->get();
        $bookGenreIds = $book->genres->pluck('id')->toArray();

        return view('books.edit', compact('book', 'genres', 'bookGenreIds'));
    }

    /**
     * 書籍情報を更新する
     */
    public function update(UpdateBookRequest $request, Book $book)
    {
        // 検証済みの入力値を取得する
        $validated = $request->validated();

        // 書籍情報を更新する
        $book->update([
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'],
            'published_date' => $validated['published_date'] ?? null,
            'description' => $validated['description'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
        ]);

        // 選択されたジャンルを関連付ける
        $book->genres()->sync($validated['genres'] ?? []);

        // 書籍詳細画面へ移動する
        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍情報を更新しました。');
    }

    /**
     * 書籍を削除する
     */
    public function destroy(Book $book)
    {
        // 関連データと書籍本体の削除をまとめて処理する
        DB::transaction(function () use ($book) {
            // 書籍に関連するレビューを取得する
            $reviews = $book->reviews;

            // レビューに関連するいいねを削除する
            foreach ($reviews as $review) {
                $review->likes()->delete();
            }

            // 書籍に関連するレビューを削除する
            $book->reviews()->delete();

            // 書籍に関連するお気に入りを削除する
            $book->favorites()->delete();

            // 書籍に関連するジャンルの関連レコードを削除する
            $book->genres()->detach();

            // 書籍を削除する
            $book->delete();
        });

        // 書籍一覧画面へ戻る
        return redirect()
            ->route('books.index')
            ->with('success', '書籍を削除しました。');
    }
}
