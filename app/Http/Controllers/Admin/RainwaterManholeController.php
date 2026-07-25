<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Factory;
use App\Models\RainwaterManhole;
use App\Models\Road;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RainwaterManholeController extends Controller
{
    // 1. Danh sách Hố ga nước mưa kèm thông tin Công ty, Xưởng, Đường
    public function index()
    {
        $user = Auth::user();

        // 🌟 Lấy danh sách hố ga bằng Eloquent kèm join bảng danh mục & convert geom
        $manholes = RainwaterManhole::query()
            ->leftJoin('companies', 'rainwater_manholes.company_id', '=', 'companies.id')
            ->leftJoin('factories', 'rainwater_manholes.factory_id', '=', 'factories.id')
            ->leftJoin('roads', 'rainwater_manholes.road_id', '=', 'roads.id')
            ->select(
                'rainwater_manholes.*',
                DB::raw('ST_AsGeoJSON(rainwater_manholes.geom) as geojson'),
                'companies.name as company_name',
                'factories.code as factory_code',
                'roads.name as road_name'
            )
            ->orderBy('rainwater_manholes.id', 'desc')
            ->get();

        // Lấy danh mục để chọn trong Modal Form
        $companies = Company::select('id', 'name')->orderBy('name', 'asc')->get();
        $factories = Factory::select('id', 'code')->orderBy('code', 'asc')->get();
        $roads = class_exists(Road::class)
            ? Road::select('id', 'name')->orderBy('name', 'asc')->get()
            : DB::table('roads')->select('id', 'name')->get();

        return Inertia::render('Admin/RainwaterManholes/Index', [
            'auth' => [
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role ?? 'company',
                ]
            ],
            'manholes'  => $manholes,
            'companies' => $companies,
            'factories' => $factories,
            'roads'     => $roads,
        ]);
    }

    // 2. Thêm Hố ga nước mưa mới
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'           => 'required|string|max:255|unique:rainwater_manholes,code',
            'company_id'     => 'nullable|exists:companies,id',
            'factory_id'     => 'nullable|exists:factories,id',
            'road_id'        => 'nullable|exists:roads,id',
            'depth'          => 'nullable|numeric|between:0,999.99',
            'elevation'      => 'nullable|numeric|between:-9999.99,9999.99',
            'cover_material' => 'nullable|string|max:255',
            'status'         => 'required|in:active,maintenance,blocked,closed',
            'note'           => 'nullable|string',
            'geojson'        => 'nullable|string',
            'geom'           => [
                'nullable',
                'string',
                'regex:/^\{\s*"type"\s*:\s*"Point"\s*,\s*"coordinates"\s*:\s*\[\s*(-?\d+(\.\d+)?)\s*,\s*(-?\d+(\.\d+)?)\s*\]\s*\}$/i'
            ],
        ], [
            'code.required' => 'Mã hố ga không được để trống',
            'code.unique'   => 'Mã hố ga này đã tồn tại',
            'geom.regex'    => 'Định dạng GeoJSON Point không hợp lệ. Ví dụ chuẩn: {"type":"Point","coordinates":[106.1732, 21.2415]}',
        ]);

        // Ưu tiên nhận geojson hoặc geom truyền lên
        $geoJson = $request->geojson ?? $request->geom;

        // 🌟 Tạo Hố ga bằng Eloquent (Kích hoạt Audit Log CREATED)
        RainwaterManhole::create([
            'code'           => $validated['code'],
            'company_id'     => $validated['company_id'] ?? null,
            'factory_id'     => $validated['factory_id'] ?? null,
            'road_id'        => $validated['road_id'] ?? null,
            'depth'          => $validated['depth'] ?? null,
            'elevation'      => $validated['elevation'] ?? null,
            'cover_material' => $validated['cover_material'] ?? null,
            'status'         => $validated['status'],
            'note'           => $validated['note'] ?? null,
            'geom'           => $geoJson ? DB::raw("ST_GeomFromGeoJSON('$geoJson')") : null,
        ]);

        return redirect()->back()->with('success', 'Thêm hố ga nước mưa thành công!');
    }

    // 3. Cập nhật Hố ga
    public function update(Request $request, $id)
    {
        $manhole = RainwaterManhole::findOrFail($id);

        $validated = $request->validate([
            'code'           => 'required|string|max:255|unique:rainwater_manholes,code,' . $manhole->id,
            'company_id'     => 'nullable|exists:companies,id',
            'factory_id'     => 'nullable|exists:factories,id',
            'road_id'        => 'nullable|exists:roads,id',
            'depth'          => 'nullable|numeric|between:0,999.99',
            'elevation'      => 'nullable|numeric|between:-9999.99,9999.99',
            'cover_material' => 'nullable|string|max:255',
            'status'         => 'required|in:active,maintenance,blocked,closed',
            'note'           => 'nullable|string',
            'geojson'        => 'nullable|string',
        ]);

        $geoJson = $request->geojson ?? $request->geom;

        // 🌟 Cập nhật Hố ga bằng Eloquent (Kích hoạt Audit Log UPDATED)
        $manhole->update([
            'code'           => $validated['code'],
            'company_id'     => $validated['company_id'] ?? null,
            'factory_id'     => $validated['factory_id'] ?? null,
            'road_id'        => $validated['road_id'] ?? null,
            'depth'          => $validated['depth'] ?? null,
            'elevation'      => $validated['elevation'] ?? null,
            'cover_material' => $validated['cover_material'] ?? null,
            'status'         => $validated['status'],
            'note'           => $validated['note'] ?? null,
            'geom'           => $geoJson ? DB::raw("ST_GeomFromGeoJSON('$geoJson')") : null,
        ]);

        return redirect()->back()->with('success', 'Cập nhật hố ga thành công!');
    }

    // 4. Xóa Hố ga
    public function destroy($id)
    {
        $manhole = RainwaterManhole::findOrFail($id);

        // 🌟 Xóa Hố ga bằng Eloquent (Kích hoạt Audit Log DELETED)
        $manhole->delete();

        return redirect()->back()->with('success', 'Đã xóa hố ga!');
    }
}