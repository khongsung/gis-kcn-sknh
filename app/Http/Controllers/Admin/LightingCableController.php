<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IndustrialZone;
use App\Models\LightingCabinet;
use App\Models\LightingCable;
use App\Models\Road;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LightingCableController extends Controller
{
    /**
     * Danh sách tuyến cáp kèm Tủ điện, KCN và Tuyến đường
     */
    public function index()
    {
        $user = Auth::user();

        $cables = LightingCable::with([
            'zone:id,name,code',
            'cabinet:id,name,code',
            'road:id,name'
        ])
        ->select(
            'lighting_cables.*',
            DB::raw('ST_AsGeoJSON(lighting_cables.geom) as geojson'),
            DB::raw('ROUND(ST_Length(lighting_cables.geom::geography)::numeric, 2) as calculated_length_m')
        )
        ->orderBy('id', 'desc')
        ->get();

        $zones = IndustrialZone::select('id', 'name', 'code')->orderBy('name', 'asc')->get();
        $cabinets = LightingCabinet::select('id', 'name', 'code', 'zone_id')->orderBy('code', 'asc')->get();
        $roads = Road::select('id', 'name', 'zone_id')->orderBy('name', 'asc')->get();

        return Inertia::render('Admin/Lighting/Cables/Index', [
            'auth' => [
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role ?? 'admin',
                ]
            ],
            'cables'   => $cables,
            'zones'    => $zones,
            'cabinets' => $cabinets,
            'roads'    => $roads,
        ]);
    }

    /**
     * Thêm mới Tuyến cáp chiếu sáng
     */
    public function store(Request $request)
    {
        $request->validate([
            'zone_id'           => 'required|exists:industrial_zones,id',
            'cabinet_id'        => 'nullable|exists:lighting_cabinets,id',
            'road_id'           => 'nullable|exists:roads,id',
            'code'              => 'required|string|max:50|unique:lighting_cables,code',
            'name'              => 'required|string|max:255',
            'cable_spec'        => 'nullable|string|max:150',
            'installation_type' => 'required|in:underground,overhead',
            'depth_m'           => 'nullable|numeric|min:0',
            'conduit_type'      => 'nullable|string|max:100',
            'status'            => 'required|in:normal,fault,repairing',
            'geojson'           => 'required|string',
        ], [
            'zone_id.required'  => 'Vui lòng chọn Khu công nghiệp.',
            'code.required'     => 'Mã tuyến cáp không được để trống.',
            'code.unique'       => 'Mã tuyến cáp này đã tồn tại.',
            'name.required'     => 'Tên tuyến cáp không được để trống.',
            'geojson.required'  => 'Chưa nhập tọa độ đường tim cáp LineString.',
        ]);

        DB::statement("
            INSERT INTO lighting_cables (
                zone_id, cabinet_id, road_id, code, name,
                cable_spec, installation_type, depth_m, conduit_type,
                length_m, status, geom, created_at, updated_at
            )
            SELECT 
                :zone_id, :cabinet_id, :road_id, :code, :name,
                :cable_spec, :installation_type, :depth_m, :conduit_type,
                ROUND(ST_Length(raw_geom::geography)::numeric, 2),
                :status, raw_geom, NOW(), NOW()
            FROM (
                SELECT ST_SetSRID(ST_GeomFromGeoJSON(:geom), 4326) AS raw_geom
            ) sub
        ", [
            'zone_id'           => $request->zone_id,
            'cabinet_id'        => $request->cabinet_id,
            'road_id'           => $request->road_id,
            'code'              => trim($request->code),
            'name'              => trim($request->name),
            'cable_spec'        => $request->cable_spec ?: 'Cu/XLPE/PVC/DSTA/PVC 4x16mm2',
            'installation_type' => $request->installation_type,
            'depth_m'           => $request->depth_m,
            'conduit_type'      => $request->conduit_type,
            'status'            => $request->status,
            'geom'              => $request->geojson,
        ]);

        return redirect()->back()->with('success', 'Thêm mới tuyến cáp chiếu sáng thành công!');
    }

    /**
     * Cập nhật Tuyến cáp
     */
    public function update(Request $request, $id)
    {
        $cable = LightingCable::findOrFail($id);

        $request->validate([
            'zone_id'           => 'required|exists:industrial_zones,id',
            'cabinet_id'        => 'nullable|exists:lighting_cabinets,id',
            'road_id'           => 'nullable|exists:roads,id',
            'code'              => 'required|string|max:50|unique:lighting_cables,code,' . $cable->id,
            'name'              => 'required|string|max:255',
            'cable_spec'        => 'nullable|string|max:150',
            'installation_type' => 'required|in:underground,overhead',
            'depth_m'           => 'nullable|numeric|min:0',
            'conduit_type'      => 'nullable|string|max:100',
            'status'            => 'required|in:normal,fault,repairing',
            'geojson'           => 'required|string',
        ]);

        DB::statement("
            UPDATE lighting_cables
            SET 
                zone_id = :zone_id,
                cabinet_id = :cabinet_id,
                road_id = :road_id,
                code = :code,
                name = :name,
                cable_spec = :cable_spec,
                installation_type = :installation_type,
                depth_m = :depth_m,
                conduit_type = :conduit_type,
                length_m = ROUND(ST_Length(ST_SetSRID(ST_GeomFromGeoJSON(:geom_calc), 4326)::geography)::numeric, 2),
                status = :status,
                geom = ST_SetSRID(ST_GeomFromGeoJSON(:geom), 4326),
                updated_at = NOW()
            WHERE id = :id
        ", [
            'id'                => $cable->id,
            'zone_id'           => $request->zone_id,
            'cabinet_id'        => $request->cabinet_id,
            'road_id'           => $request->road_id,
            'code'              => trim($request->code),
            'name'              => trim($request->name),
            'cable_spec'        => $request->cable_spec ?: 'Cu/XLPE/PVC/DSTA/PVC 4x16mm2',
            'installation_type' => $request->installation_type,
            'depth_m'           => $request->depth_m,
            'conduit_type'      => $request->conduit_type,
            'status'            => $request->status,
            'geom_calc'         => $request->geojson,
            'geom'              => $request->geojson,
        ]);

        return redirect()->back()->with('success', 'Cập nhật tuyến cáp thành công!');
    }

    /**
     * Xóa Tuyến cáp
     */
    public function destroy($id)
    {
        $cable = LightingCable::findOrFail($id);
        $cable->delete();

        return redirect()->back()->with('success', 'Đã xóa tuyến cáp chiếu sáng thành công!');
    }
}