<?php

namespace App\Http\Controllers;

use App\Models\UserAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Response\ApiResponse;

class AuthController extends Controller
{
    // Đăng ký tài khoản
    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:50|unique:user_account,username',
            'email' => 'required|email|unique:user_account,email',
            'password' => 'required|string|min:6',
            'full_name' => 'required|string|max:100',
            'dob' => 'nullable|date',
            'phone' => 'required|string|max:15|unique:user_account,phone',
        ]);

        $user = UserAccount::create([
            'user_id' => Str::uuid(),
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'full_name' => $request->full_name,
            'dob' => $request->dob,
            'phone' => $request->phone,
            'profile_picture_url' => $request->profile_picture_url ?? null,
        ]);

        return ApiResponse::success($user, 'Đăng ký tài khoản thành công', 201);
    }
}
