<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IndustrialZone;
use App\Models\SurroundingLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SurroundingLocationController extends Controller
{
    /**
     * Danh sách các vùng lân cận
     */
    public function index()
    {
        $user = Auth::user();

        $locations = SurroundingLocation::with('zone:id,name,code')
            ->select(
                'surrounding_locations.*',
                DB::raw('ST_AsGeoJSON(surrounding_locations.geom) as geojson'),
                DB::raw('ST_Y(surrounding_locations.geom) as lat'),
                DB::raw('ST_X(surrounding_locations.geom) as lng')
            )
            ->orderBy('id', 'desc')
            ->get();

        $zones = IndustrialZone::select('id', 'name', 'code')->orderBy('name', 'asc')->get();

        return Inertia::render('Admin/SurroundingLocations/Index', [
            'auth' => [
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role ?? 'admin',
                ]
            ],
            'locations' => $locations,
            'zones'     => $zones,
        ]);
    }

    /**
     * Thêm mới Vùng lân cận
     */
    public function store(Request $request)
    {
        $request->validate([
            'zone_id'     => 'nullable|exists:industrial_zones,id',
            'code'        => 'required|string|max:50|unique:surrounding_locations,code',
            'name'        => 'required|string|max:255',
            'category'    => 'required|string|max:100',
            'bg_color'    => 'required|string|max:20',
            'text_color'  => 'required|string|max:20',
            'icon'        => 'nullable|string|max:10',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
            'lat'         => 'required|numeric',
            'lng'         => 'required|numeric',
        ], [
            'code.required' => 'Mã vùng không được để trống.',
            'code.unique'   => 'Mã vùng này đã tồn tại.',
            'name.required' => 'Tên nhãn vùng lân cận không được để trống.',
            'lat.required'  => 'Vui lòng nhập vĩ độ hợp lệ.',
            'lng.required'  => 'Vui lòng nhập kinh độ hợp lệ.',
        ]);

        DB::statement("
            INSERT INTO surrounding_locations (
                zone_id, code, name, category, bg_color, text_color,
                icon, description, is_active, geom, created_at, updated_at
            ) VALUES (
                :zone_id, :code, :name, :category, :bg_color, :text_color,
                :icon, :description, :is_active,
                ST_SetSRID(ST_Point(:lng, :lat), 4326),
                NOW(), NOW()
            )
        ", [
            'zone_id'     => $request->zone_id,
            'code'        => trim($request->code),
            'name'        => trim($request->name),
            'category'    => $request->category,
            'bg_color'    => $request->bg_color ?: '#1e293b',
            'text_color'  => $request->text_color ?: '#ffffff',
            'icon'        => $request->icon ?: '📍',
            'description' => $request->description,
            'is_active'   => $request->boolean('is_active', true),
            'lng'         => $request->lng,
            'lat'         => $request->lat,
        ]);

        return redirect()->back()->with('success', 'Thêm mới vùng lân cận thành công!');
    }

    /**
     * Cập nhật Vùng lân cận
     */
    public function update(Request $request, $id)
    {
        $location = SurroundingLocation::findOrFail($id);

        $request->validate([
            'zone_id'     => 'nullable|exists:industrial_zones,id',
            'code'        => 'required|string|max:50|unique:surrounding_locations,code,' . $location->id,
            'name'        => 'required|string|max:255',
            'category'    => 'required|string|max:100',
            'bg_color'    => 'required|string|max:20',
            'text_color'  => 'required|string|max:20',
            'icon'        => 'nullable|string|max:10',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
            'lat'         => 'required|numeric',
            'lng'         => 'required|numeric',
        ]);

        DB::statement("
            UPDATE surrounding_locations
            SET 
                zone_id = :zone_id,
                code = :code,
                name = :name,
                category = :category,
                bg_color = :bg_color,
                text_color = :text_color,
                icon = :icon,
                description = :description,
                is_active = :is_active,
                geom = ST_SetSRID(ST_Point(:lng, :lat), 4326),
                updated_at = NOW()
            WHERE id = :id
        ", [
            'id'          => $location->id,
            'zone_id'     => $request->zone_id,
            'code'        => trim($request->code),
            'name'        => trim($request->name),
            'category'    => $request->category,
            'bg_color'    => $request->bg_color ?: '#1e293b',
            'text_color'  => $request->text_color ?: '#ffffff',
            'icon'        => $request->icon ?: '📍',
            'description' => $request->description,
            'is_active'   => $request->boolean('is_active', true),
            'lng'         => $request->lng,
            'lat'         => $request->lat,
        ]);

        return redirect()->back()->with('success', 'Cập nhật vùng lân cận thành công!');
    }

    /**
     * Xóa Vùng lân cận
     */
    public function destroy($id)
    {
        $location = SurroundingLocation::findOrFail($id);
        $location->delete();

        return redirect()->back()->with('success', 'Đã xóa vùng lân cận thành công!');
    }
}