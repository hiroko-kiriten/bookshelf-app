<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
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
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => [
                'required',
                'digits:13',
                'unique:books,isbn',
            ],
            'published_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image_url' => ['nullable', 'url'],
            'genres' => ['required', 'array', 'min:1'],
            'genres.*' => ['required', 'integer', 'exists:genres,id'],
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
            'published_date.required' => '出版日を入力してください。',
            'published_date.date' => '有効な出版日を入力してください。',
            'description.string' => '説明は文字列で入力してください。',
            'description.max' => '説明は1000文字以内で入力してください。',
            'image_url.url' => '有効な画像URLを入力してください。',
            'genres.required' => 'ジャンルを選択してください。',
            'genres.array' => 'ジャンルの指定が正しくありません。',
            'genres.min' => 'ジャンルを1つ以上選択してください。',
            'genres.*.required' => 'ジャンルを選択してください。',
            'genres.*.integer' => 'ジャンルの指定が正しくありません。',
            'genres.*.exists' => '選択されたジャンルが存在しません。',
        ];
    }
}
