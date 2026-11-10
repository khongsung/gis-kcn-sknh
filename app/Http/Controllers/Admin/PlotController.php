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
            'code'          => 'required|string|max:50|unique:plots,code',
            'land_type'     => 'required|in:industrial,commercial',
            'color'         => ['required', 'string', 'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/'],
            'area'          => 'nullable|numeric|min:0',
            'description'   => 'nullable|string',
            'geojson'       => 'nullable|string', 
            'company_ids'   => 'nullable|array',
            'company_ids.*' => 'exists:companies,id',
        ]);

        $geomJson = !empty($validated['geojson']) ? $validated['geojson'] : null;
        $inputArea = (isset($validated['area']) && $validated['area'] !== '' && $validated['area'] > 0) 
            ? $validated['area'] 
            : null;

        // 🌟 1. Tạo record Lô đất
        $plot = Plot::create([
            'code'        => $validated['code'],
            'land_type'   => $validated['land_type'],
            'color'       => $validated['color'],
            'total_area'  => $inputArea,
            'description' => $validated['description'] ?? null,
            'geom'        => null,
        ]);

        // 🌟 2. Đồng bộ công ty thuê
        if ($request->status === 'rented' && !empty($request->company_ids)) {
            $plot->companies()->sync($request->company_ids);
        }

        // 🌟 3. Cập nhật hình học PostGIS và TỰ ĐỘNG TÍNH DIỆN TÍCH nếu ô area để trống
        if ($geomJson) {
            if (empty($inputArea)) {
                // Tự động tính diện tích thực tế m² bằng ST_Area(geom::geography)
                DB::statement("
                    UPDATE plots 
                    SET geom = ST_SetSRID(ST_GeomFromGeoJSON(?), 4326),
                        total_area = ROUND(ST_Area(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)::geography)::numeric, 2)
                    WHERE id = ?
                ", [$geomJson, $geomJson, $plot->id]);
            } else {
                DB::statement("
                    UPDATE plots 
                    SET geom = ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)
                    WHERE id = ?
                ", [$geomJson, $plot->id]);
            }
        }

        return redirect()->back()->with('success', 'Thêm lô đất mới thành công!');
    }

    // 3. Cập nhật Lô đất
    public function update(Request $request, $id)
    {
        $plot = Plot::findOrFail($id);

        $validated = $request->validate([
            'code'          => 'required|string|max:50|unique:plots,code,' . $plot->id,
            'land_type'     => 'required|in:industrial,commercial',
            'color'         => ['required', 'string', 'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/'],
            'area'          => 'nullable|numeric|min:0',
            'description'   => 'nullable|string',
            'geojson'       => 'nullable|string',
            'company_ids'   => 'nullable|array',
            'company_ids.*' => 'exists:companies,id',
        ]);

        $geomJson = !empty($validated['geojson']) ? $validated['geojson'] : null;
        $inputArea = (isset($validated['area']) && $validated['area'] !== '' && $validated['area'] > 0) 
            ? $validated['area'] 
            : null;

        // 🌟 1. Cập nhật các trường thông tin cơ bản
        $plot->update([
            'code'        => $validated['code'],
            'land_type'   => $validated['land_type'],
            'color'       => $validated['color'],
            'total_area'  => $inputArea,
            'description' => $validated['description'] ?? null,
        ]);

        // 🌟 2. Cập nhật hình học PostGIS và TỰ ĐỘNG TÍNH LẠI DIỆN TÍCH nếu ô area để trống
        if ($geomJson) {
            if (empty($inputArea)) {
                DB::statement("
                    UPDATE plots 
                    SET geom = ST_SetSRID(ST_GeomFromGeoJSON(?), 4326),
                        total_area = ROUND(ST_Area(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)::geography)::numeric, 2)
                    WHERE id = ?
                ", [$geomJson, $geomJson, $plot->id]);
            } else {
                DB::statement("
                    UPDATE plots 
                    SET geom = ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)
                    WHERE id = ?
                ", [$geomJson, $plot->id]);
            }
        }

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