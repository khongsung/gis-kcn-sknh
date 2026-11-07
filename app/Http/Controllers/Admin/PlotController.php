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

        // 🌟 Eager load factories cùng với company của xưởng đó
        $plots = Plot::with([
                'factories' => function ($query) {
                    $query->select('id', 'plot_id', 'company_id', 'code');
                },
                'factories.company' => function ($query) {
                    $query->select('id', 'name');
                }
            ])
            ->select(
                'plots.*',
                DB::raw('ST_AsGeoJSON(plots.geom) as geojson')
            )
            ->orderBy('plots.id', 'desc')
            ->get();

        // 🌟 Gom nhóm công ty từ các factories trên từng lô đất
        $plots->transform(function ($plot) {
            // Lấy danh sách companies duy nhất từ các xưởng thuộc lô đất
            $companies = $plot->factories
                ->pluck('company')
                ->filter()         // Lọc bỏ null (nếu có xưởng trống chưa cho thuê)
                ->unique('id')     // Loại bỏ trùng lặp nếu 1 công ty thuê nhiều xưởng trên 1 lô
                ->values();

            // Gán ngược lại vào thuộc tính companies và company_ids để Vue sử dụng mượt mà
            $plot->companies = $companies;
            $plot->company_ids = $companies->pluck('id')->toArray();

            return $plot;
        });

        // Danh sách tất cả công ty cho dropdown select
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

        return redirect()->back()->with('success', 'Cập nhật lô đất thành công!');
    }

    // 4. Xóa Lô đất
    public function destroy($id)
    {
        $plot = Plot::findOrFail($id);

        // 1. Gỡ bỏ liên kết plot_id ở các nhà xưởng thuộc lô đất này
        $plot->factories()->update(['plot_id' => null]);

        // 2. Xóa lô đất
        $plot->delete();

        return redirect()->back()->with('success', 'Đã xóa lô đất!');
    }
}