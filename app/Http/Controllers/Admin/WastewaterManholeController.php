<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Factory;
use App\Models\Road;
use App\Models\WastewaterManhole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WastewaterManholeController extends Controller
{
    // 1. Danh sách Hố ga nước thải
    public function index()
    {
        $user = Auth::user();

        // 🌟 Lấy danh sách hố ga qua Eloquent Model
        $manholes = WastewaterManhole::query()
            ->leftJoin('companies', 'wastewater_manholes.company_id', '=', 'companies.id')
            ->leftJoin('factories', 'wastewater_manholes.factory_id', '=', 'factories.id')
            ->leftJoin('roads', 'wastewater_manholes.road_id', '=', 'roads.id')
            ->select(
                'wastewater_manholes.*',
                DB::raw('ST_AsGeoJSON(wastewater_manholes.geom) as geojson'),
                'companies.name as company_name',
                'factories.code as factory_code',
                'roads.name as road_name'
            )
            ->orderBy('wastewater_manholes.id', 'desc')
            ->get();

        // Lấy danh mục phục vụ Modal Form
        $companies = Company::select('id', 'name')->orderBy('name', 'asc')->get();
        $factories = Factory::select('id', 'code')->orderBy('code', 'asc')->get();
        $roads = class_exists(Road::class)
            ? Road::select('id', 'name')->orderBy('name', 'asc')->get()
            : DB::table('roads')->select('id', 'name')->get();

        return Inertia::render('Admin/WastewaterManholes/Index', [
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

    // 2. Thêm Hố ga nước thải mới
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'          => 'required|string|max:255|unique:wastewater_manholes,code',
            'company_id'    => 'nullable|exists:companies,id',
            'factory_id'    => 'nullable|exists:factories,id',
            'road_id'       => 'nullable|exists:roads,id',
            'depth'         => 'nullable|numeric|between:0,999.99',
            'elevation'     => 'nullable|numeric|between:-9999.99,9999.99',
            'pipe_diameter' => 'nullable|string|max:255',
            'status'        => 'required|in:active,maintenance,blocked,closed',
            'geojson'       => 'nullable|string',
            'geom'          => 'nullable|string',
            'note'          => 'nullable|string',
        ], [
            'code.required' => 'Mã hố ga không được để trống',
            'code.unique'   => 'Mã hố ga này đã tồn tại',
        ]);

        // Ưu tiên đọc dữ liệu GeoJSON từ request
        $geoJson = $request->geojson ?? $request->geom;

        // 🌟 Tạo Hố ga mới bằng Eloquent (Kích hoạt Audit Log CREATED)
        WastewaterManhole::create([
            'code'          => $validated['code'],
            'company_id'    => $validated['company_id'] ?? null,
            'factory_id'    => $validated['factory_id'] ?? null,
            'road_id'       => $validated['road_id'] ?? null,
            'depth'         => $validated['depth'] ?? null,
            'elevation'     => $validated['elevation'] ?? null,
            'pipe_diameter' => $validated['pipe_diameter'] ?? null,
            'status'        => $validated['status'],
            'note'          => $validated['note'] ?? null,
            'geom'          => $geoJson ? DB::raw("ST_GeomFromGeoJSON('$geoJson')") : null,
        ]);

        return redirect()->back()->with('success', 'Thêm hố ga nước thải thành công!');
    }

    // 3. Cập nhật Hố ga
    public function update(Request $request, $id)
    {
        $manhole = WastewaterManhole::findOrFail($id);

        $validated = $request->validate([
            'code'          => 'required|string|max:255|unique:wastewater_manholes,code,' . $manhole->id,
            'company_id'    => 'nullable|exists:companies,id',
            'factory_id'    => 'nullable|exists:factories,id',
            'road_id'       => 'nullable|exists:roads,id',
            'depth'         => 'nullable|numeric|between:0,999.99',
            'elevation'     => 'nullable|numeric|between:-9999.99,9999.99',
            'pipe_diameter' => 'nullable|string|max:255',
            'status'        => 'required|in:active,maintenance,blocked,closed',
            'geojson'       => 'nullable|string',
            'geom'          => 'nullable|string',
            'note'          => 'nullable|string',
        ], [
            'code.required' => 'Mã hố ga không được để trống',
            'code.unique'   => 'Mã hố ga này đã tồn tại',
        ]);

        $geoJson = $request->geojson ?? $request->geom;

        // 🌟 Cập nhật Hố ga bằng Eloquent (Kích hoạt Audit Log UPDATED)
        $manhole->update([
            'code'          => $validated['code'],
            'company_id'    => $validated['company_id'] ?? null,
            'factory_id'    => $validated['factory_id'] ?? null,
            'road_id'       => $validated['road_id'] ?? null,
            'depth'         => $validated['depth'] ?? null,
            'elevation'     => $validated['elevation'] ?? null,
            'pipe_diameter' => $validated['pipe_diameter'] ?? null,
            'status'        => $validated['status'],
            'note'          => $validated['note'] ?? null,
            'geom'          => $geoJson ? DB::raw("ST_GeomFromGeoJSON('$geoJson')") : null,
        ]);

        return redirect()->back()->with('success', 'Cập nhật hố ga thành công!');
    }

    // 4. Xóa Hố ga
    public function destroy($id)
    {
        $manhole = WastewaterManhole::findOrFail($id);

        // 🌟 Xóa Hố ga bằng Eloquent (Kích hoạt Audit Log DELETED)
        $manhole->delete();

        return redirect()->back()->with('success', 'Đã xóa hố ga!');
    }
}