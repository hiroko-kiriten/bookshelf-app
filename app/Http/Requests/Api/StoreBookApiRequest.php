<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => ['required', 'digits:13', 'unique:books,isbn'],
            'published_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'url'],
            'genres' => ['required', 'array', 'min:1'],
            'genres.*' => ['required', 'integer', 'exists:genres,id'],
        ];
    }

    public function messages(): array
    {
        return [

            'title.required' => 'タイトルを入力してください。',
            'title.string' => 'タイトルは文字列で入力してください。',
            'title.max' => 'タイトルは255文字以内で入力してください。',
            'author.required' => '著者名を入力してください。',
            'author.string' => '著者名は文字列で入力してください。',
            'author.max' => '著者名は255文字以内で入力してください。',
            'isbn.required' => 'ISBNを入力してください。',
            'isbn.digits' => 'ISBNは13桁の数字で入力してください。',
            'isbn.unique' => 'そのISBNは既に登録されています。',
            'description.string' => '説明は文字列で入力してください。',
            'published_date.required' => '出版日を入力してください。',
            'published_date.date' => '有効な出版日を入力してください。',
            'image_url.url' => '画像URLは正しい形式で入力してください。',
            'genres.required' => 'ジャンルを選択してください。',
            'genres.min' => 'ジャンルは1つ以上選択してください。',
            'genres.array' => 'ジャンルの指定が正しくありません。',
            'genres.*.required' => 'ジャンルを選択してください。',
            'genres.*.integer' => 'ジャンルの指定が正しくありません。',
            'genres.*.exists' => '指定されたジャンルは存在しません。',
        ];
    }
}
