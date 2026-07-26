<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException; // 👈 1. Import ValidationException
use Inertia\Inertia;

class AuthController extends Controller
{
    // Hiển thị trang đăng nhập
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }
        return Inertia::render('Auth/Login');
    }

    // Xử lý nút bấm Đăng nhập
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Vui lòng nhập Email',
            'email.email' => 'Email không đúng định dạng',
            'password.required' => 'Vui lòng nhập Mật khẩu',
        ]);

        if (Auth::attempt($credentials, $request->remember)) {
            $user = Auth::user();

            // Kiểm tra tài khoản có bị block không
            if ($user->is_blocked) {
                Auth::logout();
                
                // 🌟 Nâng cấp: Ném ngoại lệ Validation chuẩn cho Inertia
                throw ValidationException::withMessages([
                    'email' => 'Tài khoản này đã bị khóa. Vui lòng liên hệ Admin KCN.',
                ]);
            }

            $request->session()->regenerate();

            // Điều hướng dựa vào Role
            if ($user->role === 'admin') {
                return redirect()->intended('/admin/dashboard');
            } else {
                return redirect()->intended('/company/recruitment');
            }
        }

        // 🌟 Nâng cấp: Ném ngoại lệ khi sai Email/Mật khẩu (Tránh 419 Page Expired)
        throw ValidationException::withMessages([
            'email' => 'Email hoặc mật khẩu không chính xác.',
        ]);
    }

    // Xử lý Đăng xuất
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}