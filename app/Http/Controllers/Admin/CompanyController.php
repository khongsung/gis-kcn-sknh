<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CompanyController extends Controller
{
    // 1. Danh sách Công ty
    public function index()
    {
        $user = Auth::user();

        // 🌟 Dùng Eloquent với withCount('factories') thay cho leftJoin & DB::raw
        $companies = Company::withCount('factories')
            ->orderBy('id', 'desc')
            ->get();

        return Inertia::render('Admin/Companies/Index', [
            'auth' => [
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role ?? 'company',
                ]
            ],
            'companies' => $companies,
        ]);
    }

    // 2. Thêm Công ty mới
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'tax_code'    => 'nullable|string|max:50|unique:companies,tax_code',
            'industry'    => 'nullable|string|max:255',
            'color'       => 'nullable|string|max:20',
            'phone'       => 'nullable|string|max:50',
            'email'       => 'nullable|email|max:255',
            'description' => 'nullable|string',
        ], [
            'name.required'     => 'Vui lòng nhập Tên công ty',
            'tax_code.unique'   => 'Mã số thuế này đã tồn tại trên hệ thống',
            'email.email'       => 'Email không đúng định dạng',
        ]);

        // 🌟 Tạo mới bằng Eloquent (Tự động kích hoạt Log event CREATED)
        Company::create($validated);

        return redirect()->back()->with('success', 'Thêm công ty thành công!');
    }

    // 3. Cập nhật thông tin Công ty
    public function update(Request $request, $id)
    {
        $company = Company::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'tax_code'    => 'nullable|string|max:50|unique:companies,tax_code,' . $company->id,
            'industry'    => 'nullable|string|max:255',
            'color'       => 'nullable|string|max:20',
            'phone'       => 'nullable|string|max:50',
            'email'       => 'nullable|email|max:255',
            'description' => 'nullable|string',
        ], [
            'name.required'     => 'Vui lòng nhập Tên công ty',
            'tax_code.unique'   => 'Mã số thuế này đã tồn tại trên hệ thống',
            'email.email'       => 'Email không đúng định dạng',
        ]);

        // 🌟 Cập nhật bằng Eloquent (Tự động kích hoạt Log event UPDATED kèm dữ liệu cũ/mới)
        $company->update($validated);

        return redirect()->back()->with('success', 'Cập nhật công ty thành công!');
    }

    // 4. Xóa Công ty
    public function destroy($id)
    {
        $company = Company::findOrFail($id);

        // 🌟 Xóa bằng Eloquent (Tự động kích hoạt Log event DELETED)
        $company->delete();

        return redirect()->back()->with('success', 'Đã xóa công ty!');
    }
}