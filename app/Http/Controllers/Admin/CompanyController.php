<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\IndustrialZone;
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
            'zones'     => IndustrialZone::select('id', 'name', 'code')->get(),
        ]);
    }

    // 2. Thêm Công ty mới
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                          => 'required|string|max:255',
            'tax_code'                      => 'nullable|string|max:50|unique:companies,tax_code',
            'total_leased_area'             => 'nullable|numeric|min:0',
            'investment_certificate_number' => 'nullable|string|max:100',
            'investment_certificate_date'   => 'nullable|date',
            'lease_start_date'              => 'nullable|date',
            'lease_end_date'                => 'nullable|date|after_or_equal:lease_start_date',
            'industry'                      => 'nullable|string|max:255',
            'main_industry'                 => 'nullable|string|max:150',
            'country'                       => 'nullable|string|max:100',
            'color'                         => ['required', 'string', 'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/'],
            'phone'                         => 'nullable|string|max:50',
            'email'                         => 'nullable|email|max:100',
            'description'                   => 'nullable|string',
            'notes'                         => 'nullable|string',
            'zone_id'                       => 'nullable|exists:industrial_zones,id',
        ]);

        Company::create($validated);

        return redirect()->back()->with('success', 'Thêm công ty mới thành công!');
    }

    public function update(Request $request, $id)
    {
        $company = Company::findOrFail($id);

        $validated = $request->validate([
            'name'                          => 'required|string|max:255',
            'tax_code'                      => 'nullable|string|max:50|unique:companies,tax_code,' . $company->id,
            'total_leased_area'             => 'nullable|numeric|min:0',
            'investment_certificate_number' => 'nullable|string|max:100',
            'investment_certificate_date'   => 'nullable|date',
            'lease_start_date'              => 'nullable|date',
            'lease_end_date'                => 'nullable|date|after_or_equal:lease_start_date',
            'industry'                      => 'nullable|string|max:255',
            'main_industry'                 => 'nullable|string|max:150',
            'country'                       => 'nullable|string|max:100',
            'color'                         => ['required', 'string', 'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/'],
            'phone'                         => 'nullable|string|max:50',
            'email'                         => 'nullable|email|max:100',
            'description'                   => 'nullable|string',
            'notes'                         => 'nullable|string',
            'zone_id'                       => 'nullable|exists:industrial_zones,id',
        ]);

        $company->update($validated);

        return redirect()->back()->with('success', 'Cập nhật thông tin công ty thành công!');
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