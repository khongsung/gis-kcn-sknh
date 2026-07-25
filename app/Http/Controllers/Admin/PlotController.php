<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Plot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PlotController extends Controller
{
    // 1. Danh sách Lô đất kèm danh sách các Công ty đang thuê
    public function index()
    {
        $user = Auth::user();

        // 🌟 Lấy danh sách Lô đất bằng Eloquent với Eager Loading quan hệ 'companies'
        $plots = Plot::with(['companies' => function ($query) {
                $query->select('companies.id', 'companies.name');
            }])
            ->select(
                'plots.*',
                DB::raw('ST_AsGeoJSON(plots.geom) as geojson')
            )
            ->orderBy('plots.id', 'desc')
            ->get();

        // Map thêm mảng company_ids để Vue render dễ dàng trên Form chọn
        $plots->transform(function ($plot) {
            $plot->company_ids = $plot->companies->pluck('id')->toArray();
            return $plot;
        });

        // Danh sách công ty cho dropdown select
        $companies = Company::select('id', 'name')->orderBy('name', 'asc')->get();

        return Inertia::render('Admin/Plots/Index', [
            'auth' => [
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role ?? 'company',
                ]
            ],
            'plots'     => $plots,
            'companies' => $companies,
        ]);
    }

    // 2. Tạo Lô đất mới
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'        => 'required|string|max:50|unique:plots,code',
            'area'        => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'geojson'     => 'nullable|string',
            'company_ids' => 'nullable|array',
            'company_ids.*' => 'exists:companies,id',
        ]);

        $geomJson = $request->geojson;

        // 🌟 Tạo Lô đất bằng Eloquent Model (Kích hoạt Audit Log Event CREATED)
        $plot = Plot::create([
            'code'        => $validated['code'],
            'total_area'  => $validated['area'],
            'description' => $validated['description'] ?? null,
            'geom'        => $geomJson ? DB::raw("ST_GeomFromGeoJSON('$geomJson')") : null,
        ]);

        // 🌟 Đồng bộ mối quan hệ N-N với Công ty qua Eloquent sync()
        if ($request->status === 'rented' && !empty($request->company_ids)) {
            $plot->companies()->sync($request->company_ids);
        }

        return redirect()->back()->with('success', 'Thêm lô đất mới thành công!');
    }

    // 3. Cập nhật Lô đất
    public function update(Request $request, $id)
    {
        $plot = Plot::findOrFail($id);

        $validated = $request->validate([
            'code'        => 'required|string|max:50|unique:plots,code,' . $plot->id,
            'area'        => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'geojson'     => 'nullable|string',
            'company_ids' => 'nullable|array',
            'company_ids.*' => 'exists:companies,id',
        ]);

        $geomJson = $request->geojson;

        // 🌟 Cập nhật Lô đất bằng Eloquent Model (Kích hoạt Audit Log Event UPDATED)
        $plot->update([
            'code'        => $validated['code'],
            'total_area'  => $validated['area'],
            'description' => $validated['description'] ?? null,
            'geom'        => $geomJson ? DB::raw("ST_GeomFromGeoJSON('$geomJson')") : null,
        ]);

        // 🌟 Cập nhật bảng trung gian bằng sync(): Tự động xóa liên kết cũ và gán liên kết mới
        if ($request->status === 'rented' && !empty($request->company_ids)) {
            $plot->companies()->sync($request->company_ids);
        } else {
            $plot->companies()->detach(); // Xóa sạch nếu chuyển trạng thái không thuê
        }

        return redirect()->back()->with('success', 'Cập nhật lô đất thành công!');
    }

    // 4. Xóa Lô đất
    public function destroy($id)
    {
        $plot = Plot::findOrFail($id);

        // Hủy liên kết trong bảng trung gian trước khi xóa
        $plot->companies()->detach();

        // 🌟 Xóa bằng Eloquent Model (Kích hoạt Audit Log Event DELETED)
        $plot->delete();

        return redirect()->back()->with('success', 'Đã xóa lô đất!');
    }
}