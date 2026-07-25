<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WaterBody;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WaterBodyController extends Controller
{
    // 1. Danh sách Vùng mặt nước / Hồ điều hòa
    public function index()
    {
        $user = Auth::user();

        // 🌟 Sử dụng WaterBody Model kết hợp convert geom sang GeoJSON
        $waterBodies = WaterBody::query()
            ->select(
                'id',
                'code',
                'area',
                'description',
                'created_at',
                'updated_at',
                DB::raw('ST_AsGeoJSON(geom) as geojson')
            )
            ->orderBy('id', 'desc')
            ->get();

        return Inertia::render('Admin/WaterBodies/Index', [
            'auth' => [
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role ?? 'admin',
                ]
            ],
            'waterBodies' => $waterBodies,
        ]);
    }

    // 2. Thêm Vùng mặt nước mới
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'        => 'required|string|max:255|unique:water_bodies,code',
            'area'        => 'nullable|numeric|between:0,999999.99',
            'description' => 'nullable|string',
            'geom'        => 'nullable|string',
        ], [
            'code.required' => 'Mã khu vực mặt nước không được để trống',
            'code.unique'   => 'Mã vùng mặt nước này đã tồn tại',
        ]);

        $geomJson = $request->geom;

        // 🌟 Tạo mới bằng Eloquent Model (Tự động kích hoạt Audit Log CREATED)
        WaterBody::create([
            'code'        => $validated['code'],
            'area'        => $validated['area'] ?? null,
            'description' => $validated['description'] ?? null,
            'geom'        => $geomJson ? DB::raw("ST_GeomFromGeoJSON('$geomJson')") : null,
        ]);

        return redirect()->back()->with('success', 'Thêm diện tích mặt nước mới thành công!');
    }

    // 3. Cập nhật Vùng mặt nước
    public function update(Request $request, $id)
    {
        $waterBody = WaterBody::findOrFail($id);

        $validated = $request->validate([
            'code'        => 'required|string|max:255|unique:water_bodies,code,' . $waterBody->id,
            'area'        => 'nullable|numeric|between:0,999999.99',
            'description' => 'nullable|string',
            'geom'        => 'nullable|string',
        ]);

        $geomJson = $request->geom;

        // 🌟 Cập nhật bằng Eloquent Model (Tự động kích hoạt Audit Log UPDATED)
        $waterBody->update([
            'code'        => $validated['code'],
            'area'        => $validated['area'] ?? null,
            'description' => $validated['description'] ?? null,
            'geom'        => $geomJson ? DB::raw("ST_GeomFromGeoJSON('$geomJson')") : null,
        ]);

        return redirect()->back()->with('success', 'Cập nhật khu vực mặt nước thành công!');
    }

    // 4. Xóa Vùng mặt nước
    public function destroy($id)
    {
        $waterBody = WaterBody::findOrFail($id);

        // 🌟 Xóa bằng Eloquent Model (Tự động kích hoạt Audit Log DELETED)
        $waterBody->delete();

        return redirect()->back()->with('success', 'Đã xóa vùng mặt nước!');
    }
}