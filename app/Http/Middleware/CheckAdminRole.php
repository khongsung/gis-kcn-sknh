<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Kiểm tra đăng nhập
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // 2. Kiểm tra nếu bị block
        if ($user->is_blocked) {
            Auth::logout();
            return redirect()->route('login')->withErrors(['email' => 'Tài khoản của bạn đã bị khóa bởi Admin.']);
        }

        // 3. Kiểm tra quyền Admin
        if ($user->role !== 'admin') {
            abort(403, 'Bạn không có quyền truy cập trang quản trị này.');
        }

        return $next($request);
    }
}
