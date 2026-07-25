<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    // 1. Hiển thị danh sách User & Danh sách Công ty
    public function index()
    {
        $user = Auth::user();

        // 🌟 Dùng Eager Loading 'company' qua Eloquent thay vì leftJoin thủ công
        $users = User::with('company:id,name')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($u) {
                // Đính kèm company_name để giữ tương thích với giao diện Vue
                $u->company_name = $u->company ? $u->company->name : null;
                return $u;
            });

        // 🌟 Lấy danh sách công ty qua Model Company
        $companies = Company::select('id', 'name')->orderBy('name', 'asc')->get();

        return Inertia::render('Admin/Users/Index', [
            'auth' => [
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role ?? 'company',
                ]
            ],
            'users'     => $users,
            'companies' => $companies,
        ]);
    }

    // 2. Tạo User mới
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|string|email|max:255|unique:users',
            'password'   => 'required|string|min:6',
            'role'       => 'required|in:admin,company',
            'company_id' => 'nullable|exists:companies,id',
        ], [
            'name.required'     => 'Vui lòng nhập tên người dùng',
            'email.required'    => 'Vui lòng nhập Email',
            'email.email'       => 'Email không đúng định dạng',
            'email.unique'      => 'Email này đã tồn tại trên hệ thống',
            'password.required' => 'Vui lòng nhập mật khẩu',
            'password.min'      => 'Mật khẩu phải từ 6 ký tự trở lên',
        ]);

        // 🌟 Tạo User bằng Eloquent (Kích hoạt Audit Log CREATED)
        User::create([
            'name'       => $validated['name'],
            'email'      => $validated['email'],
            'password'   => Hash::make($validated['password']),
            'role'       => $validated['role'],
            'company_id' => $validated['role'] === 'company' ? ($validated['company_id'] ?? null) : null,
            'is_blocked' => false,
        ]);

        return redirect()->back()->with('success', 'Tạo tài khoản thành công!');
    }

    // 3. Cập nhật User
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role'       => 'required|in:admin,company',
            'company_id' => 'nullable|exists:companies,id',
            'password'   => 'nullable|string|min:6',
        ], [
            'name.required'  => 'Vui lòng nhập tên người dùng',
            'email.required' => 'Vui lòng nhập Email',
            'email.unique'   => 'Email này đã tồn tại trên hệ thống',
            'password.min'   => 'Mật khẩu phải từ 6 ký tự trở lên',
        ]);

        $data = [
            'name'       => $validated['name'],
            'email'      => $validated['email'],
            'role'       => $validated['role'],
            'company_id' => $validated['role'] === 'company' ? ($validated['company_id'] ?? null) : null,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        // 🌟 Cập nhật User bằng Eloquent (Kích hoạt Audit Log UPDATED)
        $user->update($data);

        return redirect()->back()->with('success', 'Cập nhật tài khoản thành công!');
    }

    // 4. Khóa / Mở khóa User (Block/Unblock)
    public function toggleBlock($id)
    {
        $user = User::findOrFail($id);

        // Không cho phép tự khóa tài khoản của chính mình
        if ($user->id === Auth::id()) {
            return redirect()->back()->with('error', 'Bạn không thể khóa tài khoản của chính mình!');
        }

        // 🌟 Cập nhật trạng thái qua Eloquent (Kích hoạt Audit Log UPDATED)
        $user->update([
            'is_blocked' => !$user->is_blocked
        ]);

        $msg = $user->is_blocked ? 'Đã khóa tài khoản thành công!' : 'Đã mở khóa tài khoản thành công!';
        return redirect()->back()->with('success', $msg);
    }
}