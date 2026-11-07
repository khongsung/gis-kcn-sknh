<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IndustrialZone;
use App\Models\LightingCabinet;
use App\Models\LightingPole;
use App\Models\Road;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LightingPoleController extends Controller
{
    /**
     * Danh sách cột đèn kèm KCN, Tủ điện và Tuyến đường
     */
    public function index()
    {
        $user = Auth::user();

        $poles = LightingPole::with([
            'zone:id,name,code',
            'cabinet:id,name,code',
            'road:id,name'
        ])
        ->select(
            'lighting_poles.*',
            DB::raw('ST_AsGeoJSON(lighting_poles.geom) as geojson'),
            DB::raw('ST_Y(lighting_poles.geom) as lat'),
            DB::raw('ST_X(lighting_poles.geom) as lng')
        )
        ->orderBy('id', 'desc')
        ->get();

        $zones = IndustrialZone::select('id', 'name', 'code')->orderBy('name', 'asc')->get();
        $cabinets = LightingCabinet::select('id', 'name', 'code', 'zone_id')->orderBy('code', 'asc')->get();
        $roads = Road::select('id', 'name', 'zone_id')->orderBy('name', 'asc')->get();

        return Inertia::render('Admin/Lighting/Poles/Index', [
            'auth' => [
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role ?? 'admin',
                ]
            ],
            'poles'    => $poles,
            'zones'    => $zones,
            'cabinets' => $cabinets,
            'roads'    => $roads,
        ]);
    }

    /**
     * Thêm mới Cột đèn
     */
    public function store(Request $request)
    {
        $request->validate([
            'zone_id'           => 'required|exists:industrial_zones,id',
            'cabinet_id'        => 'nullable|exists:lighting_cabinets,id',
            'road_id'           => 'nullable|exists:roads,id',
            'code'              => 'required|string|max:50|unique:lighting_poles,code',
            'height_m'          => 'nullable|numeric|min:0',
            'pole_material'     => 'nullable|string|max:100',
            'arm_type'          => 'nullable|string|max:100',
            'lamp_type'         => 'required|string|max:100',
            'wattage'           => 'nullable|numeric|min:0',
            'status'            => 'required|in:working,broken,maintenance',
            'installation_date' => 'nullable|date',
            'notes'             => 'nullable|string',
            'lat'               => 'required|numeric',
            'lng'               => 'required|numeric',
        ], [
            'zone_id.required'  => 'Vui lòng chọn Khu công nghiệp.',
            'code.required'     => 'Mã cột đèn không được để trống.',
            'code.unique'       => 'Mã cột đèn này đã tồn tại.',
            'lamp_type.required'=> 'Vui lòng chọn loại bóng đèn.',
            'lat.required'      => 'Vui lòng nhập vĩ độ mốc cột.',
            'lng.required'      => 'Vui lòng nhập kinh độ mốc cột.',
        ]);

        DB::statement("
            INSERT INTO lighting_poles (
                zone_id, cabinet_id, road_id, code, height_m,
                pole_material, arm_type, lamp_type, wattage,
                status, installation_date, notes, geom, created_at, updated_at
            ) VALUES (
                :zone_id, :cabinet_id, :road_id, :code, :height_m,
                :pole_material, :arm_type, :lamp_type, :wattage,
                :status, :installation_date, :notes,
                ST_SetSRID(ST_Point(:lng, :lat), 4326),
                NOW(), NOW()
            )
        ", [
            'zone_id'           => $request->zone_id,
            'cabinet_id'        => $request->cabinet_id,
            'road_id'           => $request->road_id,
            'code'              => trim($request->code),
            'height_m'          => $request->height_m ?: 8.0,
            'pole_material'     => $request->pole_material ?: 'Thép mạ kẽm',
            'arm_type'          => $request->arm_type ?: 'Cần đơn',
            'lamp_type'         => $request->lamp_type,
            'wattage'           => $request->wattage ?: 150,
            'status'            => $request->status,
            'installation_date' => $request->installation_date,
            'notes'             => $request->notes,
            'lng'               => $request->lng,
            'lat'               => $request->lat,
        ]);

        return redirect()->back()->with('success', 'Thêm mới cột đèn chiếu sáng thành công!');
    }

    /**
     * Cập nhật Cột đèn
     */
    public function update(Request $request, $id)
    {
        $pole = LightingPole::findOrFail($id);

        $request->validate([
            'zone_id'           => 'required|exists:industrial_zones,id',
            'cabinet_id'        => 'nullable|exists:lighting_cabinets,id',
            'road_id'           => 'nullable|exists:roads,id',
            'code'              => 'required|string|max:50|unique:lighting_poles,code,' . $pole->id,
            'height_m'          => 'nullable|numeric|min:0',
            'pole_material'     => 'nullable|string|max:100',
            'arm_type'          => 'nullable|string|max:100',
            'lamp_type'         => 'required|string|max:100',
            'wattage'           => 'nullable|numeric|min:0',
            'status'            => 'required|in:working,broken,maintenance',
            'installation_date' => 'nullable|date',
            'notes'             => 'nullable|string',
            'lat'               => 'required|numeric',
            'lng'               => 'required|numeric',
        ]);

        DB::statement("
            UPDATE lighting_poles
            SET 
                zone_id = :zone_id,
                cabinet_id = :cabinet_id,
                road_id = :road_id,
                code = :code,
                height_m = :height_m,
                pole_material = :pole_material,
                arm_type = :arm_type,
                lamp_type = :lamp_type,
                wattage = :wattage,
                status = :status,
                installation_date = :installation_date,
                notes = :notes,
                geom = ST_SetSRID(ST_Point(:lng, :lat), 4326),
                updated_at = NOW()
            WHERE id = :id
        ", [
            'id'                => $pole->id,
            'zone_id'           => $request->zone_id,
            'cabinet_id'        => $request->cabinet_id,
            'road_id'           => $request->road_id,
            'code'              => trim($request->code),
            'height_m'          => $request->height_m ?: 8.0,
            'pole_material'     => $request->pole_material ?: 'Thép mạ kẽm',
            'arm_type'          => $request->arm_type ?: 'Cần đơn',
            'lamp_type'         => $request->lamp_type,
            'wattage'           => $request->wattage ?: 150,
            'status'            => $request->status,
            'installation_date' => $request->installation_date,
            'notes'             => $request->notes,
            'lng'               => $request->lng,
            'lat'               => $request->lat,
        ]);

        return redirect()->back()->with('success', 'Cập nhật cột đèn thành công!');
    }

    /**
     * Xóa Cột đèn
     */
    public function destroy($id)
    {
        $pole = LightingPole::findOrFail($id);
        $pole->delete();

        return redirect()->back()->with('success', 'Đã xóa cột đèn thành công!');
    }
}