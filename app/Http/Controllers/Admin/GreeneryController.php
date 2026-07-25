<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Greenery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class GreeneryController extends Controller
{
    // 1. Danh sách Cây xanh / Thảm cỏ
    public function index()
    {
        $user = Auth::user();

        // 🌟 Dùng Greenery Model kết hợp convert geom sang GeoJSON
        $greenery = Greenery::query()
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

        return Inertia::render('Admin/Greenery/Index', [
            'auth' => [
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role ?? 'admin',
                ]
            ],
            'greenery' => $greenery,
        ]);
    }

    // 2. Thêm mới Khu vực Cây xanh
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'        => 'required|string|max:255|unique:greenery,code',
            'area'        => 'nullable|numeric|between:0,999999.99',
            'description' => 'nullable|string',
            'geom'        => 'nullable|string',
        ], [
            'code.required' => 'Mã khu vực cây xanh không được để trống',
            'code.unique'   => 'Mã vùng cây xanh này đã tồn tại',
        ]);

        $geomJson = $request->geom;

        // 🌟 Tạo mới bằng Eloquent Model (Tự động kích hoạt Audit Log CREATED)
        Greenery::create([
            'code'        => $validated['code'],
            'area'        => $validated['area'] ?? null,
            'description' => $validated['description'] ?? null,
            'geom'        => $geomJson ? DB::raw("ST_GeomFromGeoJSON('$geomJson')") : null,
        ]);

        return redirect()->back()->with('success', 'Thêm vùng cây xanh mới thành công!');
    }

    // 3. Cập nhật Khu vực Cây xanh
    public function update(Request $request, $id)
    {
        $greenery = Greenery::findOrFail($id);

        $validated = $request->validate([
            'code'        => 'required|string|max:255|unique:greenery,code,' . $greenery->id,
            'area'        => 'nullable|numeric|between:0,999999.99',
            'description' => 'nullable|string',
            'geom'        => 'nullable|string',
        ]);

        $geomJson = $request->geom;

        // 🌟 Cập nhật bằng Eloquent Model (Tự động kích hoạt Audit Log UPDATED)
        $greenery->update([
            'code'        => $validated['code'],
            'area'        => $validated['area'] ?? null,
            'description' => $validated['description'] ?? null,
            'geom'        => $geomJson ? DB::raw("ST_GeomFromGeoJSON('$geomJson')") : null,
        ]);

        return redirect()->back()->with('success', 'Cập nhật khu vực cây xanh thành công!');
    }

    // 4. Xóa Khu vực Cây xanh
    public function destroy($id)
    {
        $greenery = Greenery::findOrFail($id);

        // 🌟 Xóa bằng Eloquent Model (Tự động kích hoạt Audit Log DELETED)
        $greenery->delete();

        return redirect()->back()->with('success', 'Đã xóa vùng cây xanh!');
    }
}