<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReviewRequest extends FormRequest
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
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:1', 'max:1000'],
        ];
    }

    /**
     * エラーメッセージを定義する
     */
    public function messages(): array
    {
        return [
            'rating.required' => '評価を入力してください。',
            'rating.integer' => '評価は整数で入力してください。',
            'rating.between' => '評価は1〜5の範囲で入力してください。',
            'comment.required' => 'コメントを入力してください。',
            'comment.string' => 'コメントは文字列で入力してください。',
            'comment.min' => 'コメントは1文字以上で入力してください。',
            'comment.max' => 'コメントは1000文字以内で入力してください。',
        ];
    }
}
