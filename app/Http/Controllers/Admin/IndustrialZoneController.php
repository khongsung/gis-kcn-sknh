<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IndustrialZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class IndustrialZoneController extends Controller
{
    /**
     * Danh sách Khu công nghiệp kèm thống kê hạ tầng
     */
    public function index()
    {
        $user = Auth::user();

        // Lấy danh sách KCN kèm tọa độ tâm WGS84
        $zones = IndustrialZone::select(
            'industrial_zones.*',
            DB::raw('ST_Y(industrial_zones.center_geom) as lat'),
            DB::raw('ST_X(industrial_zones.center_geom) as lng')
        )
        ->withCount([
            'factories',
            'plots',
            'surroundingLocations',
        ])
        ->orderBy('id', 'asc')
        ->get();

        return Inertia::render('Admin/IndustrialZones/Index', [
            'auth' => [
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role ?? 'admin',
                ]
            ],
            'zones' => $zones,
        ]);
    }

    /**
     * Thêm mới Khu công nghiệp
     */
    public function store(Request $request)
    {
        $request->validate([
            'code'         => 'required|string|max:50|unique:industrial_zones,code',
            'name'         => 'required|string|max:255',
            'province'     => 'nullable|string|max:100',
            'total_area'   => 'nullable|numeric|min:0',
            'default_zoom' => 'required|integer|min:10|max:20',
            'description'  => 'nullable|string',
            'is_active'    => 'boolean',
            'lat'          => 'required|numeric',
            'lng'          => 'required|numeric',
        ], [
            'code.required' => 'Mã KCN không được để trống.',
            'code.unique'   => 'Mã KCN này đã tồn tại trong hệ thống.',
            'name.required' => 'Tên KCN không được để trống.',
            'lat.required'  => 'Vui lòng nhập vĩ độ tâm bản đồ.',
            'lng.required'  => 'Vui lòng nhập kinh độ tâm bản đồ.',
        ]);

        DB::statement("
            INSERT INTO industrial_zones (
                code, name, province, total_area, default_zoom, description, is_active,
                center_geom, created_at, updated_at
            ) VALUES (
                :code, :name, :province, :total_area, :default_zoom, :description, :is_active,
                ST_SetSRID(ST_Point(:lng, :lat), 4326),
                NOW(), NOW()
            )
        ", [
            'code'         => strtoupper(trim($request->code)),
            'name'         => trim($request->name),
            'province'     => $request->province ?: 'Bắc Giang',
            'total_area'   => $request->total_area ?: 0,
            'default_zoom' => $request->default_zoom ?: 15,
            'description'  => $request->description,
            'is_active'    => $request->boolean('is_active', true),
            'lng'          => $request->lng,
            'lat'          => $request->lat,
        ]);

        return redirect()->back()->with('success', 'Thêm mới Khu công nghiệp thành công!');
    }

    /**
     * Cập nhật thông tin Khu công nghiệp
     */
    public function update(Request $request,$id)
    {
        $zone = IndustrialZone::findOrFail($id);

        $request->validate([
            'code'         => 'required|string|max:50|unique:industrial_zones,code,' . $zone->id,
            'name'         => 'required|string|max:255',
            'province'     => 'nullable|string|max:100',
            'total_area'   => 'nullable|numeric|min:0',
            'default_zoom' => 'required|integer|min:10|max:20',
            'description'  => 'nullable|string',
            'is_active'    => 'boolean',
            'lat'          => 'required|numeric',
            'lng'          => 'required|numeric',
        ]);

        DB::statement("
            UPDATE industrial_zones
            SET 
                code = :code,
                name = :name,
                province = :province,
                total_area = :total_area,
                default_zoom = :default_zoom,
                description = :description,
                is_active = :is_active,
                center_geom = ST_SetSRID(ST_Point(:lng, :lat), 4326),
                updated_at = NOW()
            WHERE id = :id
        ", [
            'id'           => $zone->id,
            'code'         => strtoupper(trim($request->code)),
            'name'         => trim($request->name),
            'province'     => $request->province ?: 'Bắc Giang',
            'total_area'   => $request->total_area ?: 0,
            'default_zoom' => $request->default_zoom ?: 15,
            'description'  => $request->description,
            'is_active'    => $request->boolean('is_active', true),
            'lng'          => $request->lng,
            'lat'          => $request->lat,
        ]);

        return redirect()->back()->with('success', 'Cập nhật Khu công nghiệp thành công!');
    }

    /**
     * Xóa Khu công nghiệp (Ràng buộc an toàn hạ tầng)
     */
    public function destroy($id)
    {
        $zone = IndustrialZone::withCount(['factories', 'plots'])->findOrFail($id);

        if ($zone->factories_count > 0 || $zone->plots_count > 0) {
            return redirect()->back()->withErrors([
                'delete_error' => "Không thể xóa KCN [{$zone->name}] vì đang chứa {$zone->factories_count} nhà xưởng và {$zone->plots_count} lô đất. Vui lòng chuyển dữ liệu sang KCN khác trước."
            ]);
        }

        $zone->delete();

        return redirect()->back()->with('success', 'Đã xóa Khu công nghiệp thành công!');
    }
}