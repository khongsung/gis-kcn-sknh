<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Road;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RoadController extends Controller
{
    // 1. Danh sách các tuyến đường
    public function index()
    {
        $user = Auth::user();

        // 🌟 Truy vấn danh sách tuyến đường qua Eloquent Model kết hợp ép GeoJSON
        $roads = Road::query()
            ->select(
                'roads.*',
                DB::raw('ST_AsGeoJSON(roads.geom) as geojson')
            )
            ->orderBy('id', 'desc')
            ->get();

        return Inertia::render('Admin/Roads/Index', [
            'auth' => [
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role ?? 'company',
                ]
            ],
            'roads' => $roads,
        ]);
    }

    // 2. Thêm Tuyến đường mới
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'right_of_way' => 'nullable|numeric|between:0,999999.99',
            'road_width'   => 'nullable|numeric|between:0,999999.99',
            'geojson'      => 'nullable|string',
            'geom'         => 'nullable|string',
        ], [
            'name.required' => 'Vui lòng nhập Tên đường',
        ]);

        // Hỗ trợ linh hoạt cả 2 tên field request từ frontend
        $geomJson = $request->geojson ?? $request->geom;

        // 🌟 Tạo Tuyến đường mới qua Eloquent (Kích hoạt Audit Log CREATED)
        Road::create([
            'name'         => $validated['name'],
            'right_of_way' => $validated['right_of_way'] ?? null,
            'road_width'   => $validated['road_width'] ?? null,
            'geom'         => $geomJson ? DB::raw("ST_GeomFromGeoJSON('$geomJson')") : null,
        ]);

        return redirect()->back()->with('success', 'Thêm tuyến đường thành công!');
    }

    // 3. Cập nhật Tuyến đường
    public function update(Request $request, $id)
    {
        $road = Road::findOrFail($id);

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'right_of_way' => 'nullable|numeric|between:0,999999.99',
            'road_width'   => 'nullable|numeric|between:0,999999.99',
            'geojson'      => 'nullable|string',
            'geom'         => 'nullable|string',
        ], [
            'name.required' => 'Vui lòng nhập Tên đường',
        ]);

        $geomJson = $request->geojson ?? $request->geom;

        // 🌟 Cập nhật Tuyến đường qua Eloquent (Kích hoạt Audit Log UPDATED)
        $road->update([
            'name'         => $validated['name'],
            'right_of_way' => $validated['right_of_way'] ?? null,
            'road_width'   => $validated['road_width'] ?? null,
            'geom'         => $geomJson ? DB::raw("ST_GeomFromGeoJSON('$geomJson')") : null,
        ]);

        return redirect()->back()->with('success', 'Cập nhật tuyến đường thành công!');
    }

    // 4. Xóa Tuyến đường
    public function destroy($id)
    {
        $road = Road::findOrFail($id);

        // 🌟 Xóa Tuyến đường qua Eloquent (Kích hoạt Audit Log DELETED)
        $road->delete();

        return redirect()->back()->with('success', 'Đã xóa tuyến đường!');
    }
}