<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Factory;
use App\Models\Plot;
use App\Models\IndustrialZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class FactoryController extends Controller
{
    // 1. Danh sách Nhà xưởng
    public function index()
    {
        $user = Auth::user();

        // 🌟 Lấy danh sách xưởng kèm tên công ty, mã lô đất và convert geom sang GeoJSON bằng Eloquent
        $factories = Factory::query()
            ->leftJoin('companies', 'factories.company_id', '=', 'companies.id')
            ->leftJoin('plots', 'factories.plot_id', '=', 'plots.id')
            ->select(
                'factories.*',
                DB::raw('ST_AsGeoJSON(factories.geom) as geojson'),
                'companies.name as company_name',
                'plots.code as plot_code'
            )
            ->orderBy('factories.id', 'desc')
            ->get();

        // Dữ liệu dropdown để chọn khi Thêm/Sửa
        $companies = Company::select('id', 'name')->orderBy('name', 'asc')->get();
        
        // Sử dụng DB::table nếu chưa tạo Model Plot, hoặc Plot::select nếu đã có Model
        $plots = class_exists(Plot::class) 
            ? Plot::select('id', 'code', 'zone_id')->orderBy('code', 'asc')->get()
            : DB::table('plots')->select('id', 'code')->get();

        $zones = IndustrialZone::select('id', 'name', 'code')->get();

        return Inertia::render('Admin/Factories/Index', [
            'auth'      => ['user' => $user],
            'factories' => $factories,
            'companies' => $companies,
            'plots'     => $plots,
            'zones'     => $zones,
        ]);
    }

    // 2. Thêm Nhà xưởng mới
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'       => 'required|string|max:50|unique:factories,code',
            'area'       => 'nullable|numeric|min:0',
            'status'     => 'required|in:available,rented,maintenance',
            'plot_id'    => 'nullable|exists:plots,id',
            'company_id' => 'nullable|exists:companies,id',
            'geojson'    => 'nullable|string',
            'zone_id' => 'required|exists:industrial_zones,id'
        ], [
            'code.required' => 'Mã nhà xưởng là bắt buộc',
            'code.unique'   => 'Mã nhà xưởng đã tồn tại',
        ]);

        $geoJson = !empty($validated['geojson']) ? $validated['geojson'] : null;
        $inputArea = (isset($validated['area']) && $validated['area'] !== '' && $validated['area'] > 0)
            ? $validated['area']
            : null;

        // 🌟 Thêm mới bằng Eloquent Model (Kích hoạt Audit Log Event CREATED)
        Factory::create([
            'code'       => $validated['code'],
            'area'       => $validated['area'] ?? null,
            'status'     => $validated['status'],
            'plot_id'    => $validated['plot_id'] ?? null,
            'company_id' => $validated['status'] === 'rented' ? ($validated['company_id'] ?? null) : null,
            'zone_id'     => $validated['zone_id'],
            'geom'       => $geoJson ? DB::raw("ST_GeomFromGeoJSON('$geoJson')") : null,
        ]);

        // 2. Gán Polygon và tự động tính diện tích nếu không nhập tay
        if ($geoJson) {
            if (empty($inputArea)) {
                // Tự động tính diện tích mét vuông bằng ST_Area trắc địa
                DB::statement("
                    UPDATE factories 
                    SET geom = ST_SetSRID(ST_GeomFromGeoJSON(?), 4326),
                        area = ROUND(ST_Area(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)::geography)::numeric, 2)
                    WHERE id = ?
                ", [$geoJson, $geoJson, $factory->id]);
            } else {
                DB::statement("
                    UPDATE factories 
                    SET geom = ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)
                    WHERE id = ?
                ", [$geoJson, $factory->id]);
            }
        }

        return redirect()->back()->with('success', 'Thêm nhà xưởng thành công!');
    }

    // 3. Cập nhật Nhà xưởng
    public function update(Request $request, $id)
    {
        $factory = Factory::findOrFail($id);

        $validated = $request->validate([
            'code'       => 'required|string|max:50|unique:factories,code,' . $factory->id,
            'area'       => 'nullable|numeric|min:0',
            'status'     => 'required|in:available,rented,maintenance',
            'plot_id'    => 'nullable|exists:plots,id',
            'company_id' => 'nullable|exists:companies,id',
            'geojson'    => 'nullable|string',
            'zone_id' => 'required|exists:industrial_zones,id'
        ], [
            'code.required' => 'Mã nhà xưởng là bắt buộc',
            'code.unique'   => 'Mã nhà xưởng đã tồn tại',
        ]);

        $geoJson = !empty($validated['geojson']) ? $validated['geojson'] : null;
        $inputArea = (isset($validated['area']) && $validated['area'] !== '' && $validated['area'] > 0)
            ? $validated['area']
            : null;

        // 🌟 Cập nhật bằng Eloquent Model (Kích hoạt Audit Log Event UPDATED kèm so sánh dữ liệu cũ/mới)
        $factory->update([
            'code'       => $validated['code'],
            'area'       => $validated['area'] ?? null,
            'status'     => $validated['status'],
            'plot_id'    => $validated['plot_id'] ?? null,
            'company_id' => $validated['status'] === 'rented' ? ($validated['company_id'] ?? null) : null,
            'geom'       => $geoJson ? DB::raw("ST_GeomFromGeoJSON('$geoJson')") : null,
            'zone_id'     => $validated['zone_id'],
        ]);

        if ($geoJson) {
            if (empty($inputArea)) {
                DB::statement("
                    UPDATE factories 
                    SET geom = ST_SetSRID(ST_GeomFromGeoJSON(?), 4326),
                        area = ROUND(ST_Area(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)::geography)::numeric, 2)
                    WHERE id = ?
                ", [$geoJson, $geoJson, $factory->id]);
            } else {
                DB::statement("
                    UPDATE factories 
                    SET geom = ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)
                    WHERE id = ?
                ", [$geoJson, $factory->id]);
            }
        }

        return redirect()->back()->with('success', 'Cập nhật nhà xưởng thành công!');
    }

    // 4. Xóa Nhà xưởng
    public function destroy($id)
    {
        $factory = Factory::findOrFail($id);

        // 🌟 Xóa bằng Eloquent Model (Kích hoạt Audit Log Event DELETED)
        $factory->delete();

        return redirect()->back()->with('success', 'Đã xóa nhà xưởng!');
    }
}