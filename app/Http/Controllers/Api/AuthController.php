<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * 現在使用しているAPIトークンを無効化する
     */
    public function destroyToken(Request $request)
    {
        // 現在使用しているAPIトークンを削除する
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'APIトークンを無効化しました。',
        ]);
    }
}
