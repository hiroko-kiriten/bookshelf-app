<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    /**
     * リクエストを許可する
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルールを定義する
     */
    public function rules(): array
    {
        $book = $this->route('book');

        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => [
                'required',
                'digits:13',
                Rule::unique('books', 'isbn')->ignore($book->id),
            ],
            'published_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'genres' => ['nullable', 'array'],
            'genres.*' => ['exists:genres,id'],
        ];
    }

    /**
     * エラーメッセージを定義する
     */
    public function messages(): array
    {
        return [
            'title.required' => '書籍名を入力してください。',
            'title.string' => '書籍名は文字列で入力してください。',
            'title.max' => '書籍名は255文字以内で入力してください。',
            'author.required' => '著者名を入力してください。',
            'author.string' => '著者名は文字列で入力してください。',
            'author.max' => '著者名は255文字以内で入力してください。',
            'isbn.required' => 'ISBNを入力してください。',
            'isbn.digits' => 'ISBNは13桁の数字で入力してください。',
            'isbn.unique' => 'そのISBNは既に登録されています。',
            'published_date.date' => '有効な出版日を入力してください。',
            'image_url.url' => '有効な画像URLを入力してください。',
            'image_url.max' => '画像URLは2048文字以内で入力してください。',
            'genres.array' => 'ジャンルの指定が正しくありません。',
            'genres.*.exists' => '選択されたジャンルが存在しません。',
        ];
    }
}
