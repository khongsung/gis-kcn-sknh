<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IndustrialZone;
use App\Models\LightingCabinet;
use App\Models\Road;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LightingCabinetController extends Controller
{
    /**
     * Danh sách tủ điện kèm dữ liệu KCN và Tuyến đường
     */
    public function index()
    {
        $user = Auth::user();

        // Lấy danh sách tủ điện, convert Point sang lat, lng và GeoJSON
        $cabinets = LightingCabinet::with([
            'zone:id,name,code',
            'road:id,name'
        ])
        ->withCount(['poles', 'cables']) // Đếm số cột đèn và lộ cáp trực thuộc
        ->select(
            'lighting_cabinets.*',
            DB::raw('ST_AsGeoJSON(lighting_cabinets.geom) as geojson'),
            DB::raw('ST_Y(lighting_cabinets.geom) as lat'),
            DB::raw('ST_X(lighting_cabinets.geom) as lng')
        )
        ->orderBy('id', 'desc')
        ->get();

        $zones = IndustrialZone::select('id', 'name', 'code')->orderBy('name', 'asc')->get();$roads = Road::select('id', 'name', 'zone_id')->orderBy('name', 'asc')->get();

        return Inertia::render('Admin/Lighting/Cabinets/Index', [
            'auth' => [
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role ?? 'admin',
                ]
            ],
            'cabinets' => $cabinets,
            'zones'    => $zones,
            'roads'    => $roads,
        ]);
    }

    /**
     * Thêm mới Tủ điện
     */
    public function store(Request $request)
    {
        $request->validate([
            'zone_id'        => 'required|exists:industrial_zones,id',
            'road_id'        => 'nullable|exists:roads,id',
            'code'           => 'required|string|max:50|unique:lighting_cabinets,code',
            'name'           => 'required|string|max:255',
            'capacity_kva'   => 'nullable|numeric|min:0',
            'control_type'   => 'required|string',
            'power_source'   => 'nullable|string|max:255',
            'channels_count' => 'nullable|integer|min:1',
            'status'         => 'required|in:active,maintenance,inactive',
            'installation_date' => 'nullable|date',
            'notes'          => 'nullable|string',
            'lat'            => 'required|numeric',
            'lng'            => 'required|numeric',
        ], [
            'zone_id.required' => 'Vui lòng chọn Khu công nghiệp.',
            'code.required'    => 'Mã tủ điện không được để trống.',
            'code.unique'      => 'Mã tủ điện này đã tồn tại.',
            'name.required'    => 'Tên tủ điện không được để trống.',
            'lat.required'     => 'Vui lòng nhập vĩ độ hợp lệ.',
            'lng.required'     => 'Vui lòng nhập kinh độ hợp lệ.',
        ]);

        DB::statement("
            INSERT INTO lighting_cabinets (
                zone_id, road_id, code, name, capacity_kva,
                control_type, power_source, channels_count,
                status, installation_date, notes, geom, created_at, updated_at
            ) VALUES (
                :zone_id, :road_id, :code, :name, :capacity_kva,
                :control_type, :power_source, :channels_count,
                :status, :installation_date, :notes,
                ST_SetSRID(ST_Point(:lng, :lat), 4326),
                NOW(), NOW()
            )
        ", [
            'zone_id'           => $request->zone_id,
            'road_id'           => $request->road_id,
            'code'              => trim($request->code),
            'name'              => trim($request->name),
            'capacity_kva'      => $request->capacity_kva,
            'control_type'      => $request->control_type,
            'power_source'      => $request->power_source,
            'channels_count'    => $request->channels_count ?: 2,
            'status'            => $request->status,
            'installation_date' => $request->installation_date,
            'notes'             => $request->notes,
            'lng'               => $request->lng,
            'lat'               => $request->lat,
        ]);

        return redirect()->back()->with('success', 'Thêm mới tủ điện chiếu sáng thành công!');
    }

    /**
     * Cập nhật Tủ điện
     */
    public function update(Request $request,$id)
    {
        $cabinet = LightingCabinet::findOrFail($id);

        $request->validate([
            'zone_id'        => 'required|exists:industrial_zones,id',
            'road_id'        => 'nullable|exists:roads,id',
            'code'           => 'required|string|max:50|unique:lighting_cabinets,code,' . $cabinet->id,
            'name'           => 'required|string|max:255',
            'capacity_kva'   => 'nullable|numeric|min:0',
            'control_type'   => 'required|string',
            'power_source'   => 'nullable|string|max:255',
            'channels_count' => 'nullable|integer|min:1',
            'status'         => 'required|in:active,maintenance,inactive',
            'installation_date' => 'nullable|date',
            'notes'          => 'nullable|string',
            'lat'            => 'required|numeric',
            'lng'            => 'required|numeric',
        ]);

        DB::statement("
            UPDATE lighting_cabinets
            SET 
                zone_id = :zone_id,
                road_id = :road_id,
                code = :code,
                name = :name,
                capacity_kva = :capacity_kva,
                control_type = :control_type,
                power_source = :power_source,
                channels_count = :channels_count,
                status = :status,
                installation_date = :installation_date,
                notes = :notes,
                geom = ST_SetSRID(ST_Point(:lng, :lat), 4326),
                updated_at = NOW()
            WHERE id = :id
        ", [
            'id'                => $cabinet->id,
            'zone_id'           => $request->zone_id,
            'road_id'           => $request->road_id,
            'code'              => trim($request->code),
            'name'              => trim($request->name),
            'capacity_kva'      => $request->capacity_kva,
            'control_type'      => $request->control_type,
            'power_source'      => $request->power_source,
            'channels_count'    => $request->channels_count ?: 2,
            'status'            => $request->status,
            'installation_date' => $request->installation_date,
            'notes'             => $request->notes,
            'lng'               => $request->lng,
            'lat'               => $request->lat,
        ]);

        return redirect()->back()->with('success', 'Cập nhật tủ điện thành công!');
    }

    /**
     * Xóa Tủ điện
     */
    public function destroy($id)
    {
        $cabinet = LightingCabinet::findOrFail($id);$cabinet->delete();

        return redirect()->back()->with('success', 'Đã xóa tủ điện thành công!');
    }
}