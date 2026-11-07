<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IndustrialZone;
use App\Models\IndustrialZoneBoundary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class IndustrialZoneBoundaryController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Lấy danh sách ranh giới, chuyển geom sang GeoJSON
        $boundaries = IndustrialZoneBoundary::with('zone:id,name,code')
            ->select(
                'industrial_zone_boundaries.*',
                DB::raw('ST_AsGeoJSON(industrial_zone_boundaries.geom) as geojson')
            )
            ->orderBy('id', 'desc')
            ->get();

        $zones = IndustrialZone::select('id', 'name', 'code')->orderBy('name', 'asc')->get();

        return Inertia::render('Admin/Boundaries/Index', [
            'auth' => [
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role ?? 'admin',
                ]
            ],
            'boundaries' => $boundaries,
            'zones'      => $zones,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'zone_id'         => 'required|exists:industrial_zones,id',
            'code'            => 'required|string|max:50|unique:industrial_zone_boundaries,code',
            'name'            => 'required|string|max:255',
            'decision_number' => 'nullable|string|max:100',
            'approval_date'   => 'nullable|date',
            'legal_basis'     => 'nullable|string',
            'fill_color'      => 'nullable|string|max:20',
            'geojson'         => 'required|string',
        ]);

        $fillColor = $request->fill_color ?: '#e11d48';

        DB::statement("
            INSERT INTO industrial_zone_boundaries (
                zone_id, code, name, decision_number, approval_date,
                legal_basis, fill_color, geom, created_at, updated_at
            ) VALUES (
                :zone_id, :code, :name, :decision_number, :approval_date,
                :legal_basis, :fill_color,
                ST_SetSRID(ST_GeomFromGeoJSON(:geom), 4326),
                NOW(), NOW()
            )
        ", [
            'zone_id'         => $request->zone_id,
            'code'            => trim($request->code),
            'name'            => trim($request->name),
            'decision_number' => $request->decision_number,
            'approval_date'   => $request->approval_date,
            'legal_basis'     => $request->legal_basis,
            'fill_color'      => $fillColor,
            'geom'            => $request->geojson,
        ]);

        return redirect()->back()->with('success', 'Thêm mới ranh giới thành công!');
    }

    public function update(Request $request, $id)
    {
        $boundary = IndustrialZoneBoundary::findOrFail($id);

        $request->validate([
            'zone_id'         => 'required|exists:industrial_zones,id',
            'code'            => 'required|string|max:50|unique:industrial_zone_boundaries,code,' . $boundary->id,
            'name'            => 'required|string|max:255',
            'decision_number' => 'nullable|string|max:100',
            'approval_date'   => 'nullable|date',
            'legal_basis'     => 'nullable|string',
            'fill_color'      => 'nullable|string|max:20',
            'geojson'         => 'required|string',
        ]);

        $fillColor = $request->fill_color ?: '#e11d48';

        DB::statement("
            UPDATE industrial_zone_boundaries
            SET 
                zone_id = :zone_id,
                code = :code,
                name = :name,
                decision_number = :decision_number,
                approval_date = :approval_date,
                legal_basis = :legal_basis,
                fill_color = :fill_color,
                geom = ST_SetSRID(ST_GeomFromGeoJSON(:geom), 4326),
                updated_at = NOW()
            WHERE id = :id
        ", [
            'id'              => $boundary->id,
            'zone_id'         => $request->zone_id,
            'code'            => trim($request->code),
            'name'            => trim($request->name),
            'decision_number' => $request->decision_number,
            'approval_date'   => $request->approval_date,
            'legal_basis'     => $request->legal_basis,
            'fill_color'      => $fillColor,
            'geom'            => $request->geojson,
        ]);

        return redirect()->back()->with('success', 'Cập nhật ranh giới thành công!');
    }

    public function destroy($id)
    {
        $boundary = IndustrialZoneBoundary::findOrFail($id);
        $boundary->delete();

        return redirect()->back()->with('success', 'Đã xóa ranh giới thành công!');
    }
}