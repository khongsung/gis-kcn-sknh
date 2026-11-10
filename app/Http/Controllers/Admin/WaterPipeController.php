<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IndustrialZone;
use App\Models\RainwaterManhole;
use App\Models\Road;
use App\Models\WastewaterManhole;
use App\Models\WaterPipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class WaterPipeController extends Controller
{
    public function index(Request $request)
    {
        $query = WaterPipe::with(['zone:id,code,name', 'road:id,name'])
            ->select(
                'water_pipes.*',
                DB::raw('ST_AsText(geom) as geom_wkt'),
                DB::raw('ROUND(ST_Length(geom::geography)::numeric, 2) as length_m')
            );

        if ($request->filled('zone_id')) {
            $query->where('zone_id', $request->zone_id);
        }
        if ($request->filled('pipe_type')) {
            $query->where('pipe_type', $request->pipe_type);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'ILIKE', "%{$search}%")
                  ->orWhere('name', 'ILIKE', "%{$search}%");
            });
        }

        $pipes = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        // Danh mục cơ bản
        $zones = IndustrialZone::where('is_active', true)->select('id', 'code', 'name')->get();
        $roads = Road::select('id', 'name', 'zone_id')->get();

        // 🌟 Lấy danh sách hố ga nước thải (kèm Lat, Lng và Cốt đáy)
        $wastewaterManholes = DB::table('wastewater_manholes')
            ->select(
                'id',
                'code',
                'zone_id',
                'elevation', // Cột cốt đáy (nếu trong DB đặt tên là invert_elevation thì đổi tên tương ứng)
                DB::raw('ST_Y(geom) as lat'),
                DB::raw('ST_X(geom) as lng')
            )
            ->whereNotNull('geom')
            ->get();

        // 🌟 Lấy danh sách hố ga nước mưa (kèm Lat, Lng và Cốt đáy)
        $rainwaterManholes = DB::table('rainwater_manholes')
            ->select(
                'id',
                'code',
                'zone_id',
                'elevation', // Cột cốt đáy
                DB::raw('ST_Y(geom) as lat'),
                DB::raw('ST_X(geom) as lng')
            )
            ->whereNotNull('geom')
            ->get();

        $user = Auth::user();

        return Inertia::render('Admin/WaterPipes/Index', [
        	'auth' => [
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role ?? 'admin',
                ]
            ],
            'pipes'              => $pipes,
            'zones'              => $zones,
            'roads'              => $roads,
            'wastewaterManholes' => $wastewaterManholes,
            'rainwaterManholes'  => $rainwaterManholes,
            'filters'            => $request->only(['search', 'zone_id', 'pipe_type'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'zone_id'                 => 'nullable|exists:industrial_zones,id',
            'road_id'                 => 'nullable|exists:roads,id',
            'pipe_type'               => 'required|in:wastewater,rainwater',
            'code'                    => 'required|string|max:50|unique:water_pipes,code',
            'name'                    => 'nullable|string|max:255',
            'start_manhole_id'        => 'nullable|integer',
            'end_manhole_id'          => 'nullable|integer',
            'cross_section'           => 'required|in:circular,box,open_channel',
            'diameter'                => 'nullable|integer',
            'width'                   => 'nullable|integer',
            'height'                  => 'nullable|integer',
            'material'                => 'nullable|string|max:50',
            'invert_start_elevation'  => 'nullable|numeric',
            'invert_end_elevation'    => 'nullable|numeric',
            'slope'                   => 'nullable|numeric',
            'status'                  => 'required|in:active,clogged,damaged,maintenance',
            'geom'                    => 'nullable|string',
        ]);

        $geomGeoJson = $validated['geom'] ?? null;
        unset($validated['geom']);

        $pipe = WaterPipe::create($validated);

        if (!empty($geomGeoJson)) {
            DB::statement("
                UPDATE water_pipes 
                SET geom = ST_Multi(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)) 
                WHERE id = ?
            ", [$geomGeoJson, $pipe->id]);
        }

        // 🌟 Đồng bộ cốt đáy ngược lại vào bảng hố ga nếu hố ga chưa có
        $this->syncManholeElevation(
            $validated['pipe_type'], 
            $validated['start_manhole_id'] ?? null, 
            $validated['invert_start_elevation'] ?? null
        );
        $this->syncManholeElevation(
            $validated['pipe_type'], 
            $validated['end_manhole_id'] ?? null, 
            $validated['invert_end_elevation'] ?? null
        );

        return redirect()->back()->with('success', 'Tạo tuyến cống mới thành công!');
    }

    public function update(Request $request, $id)
    {
        $pipe = WaterPipe::findOrFail($id);

        $validated = $request->validate([
            'zone_id'                 => 'nullable|exists:industrial_zones,id',
            'road_id'                 => 'nullable|exists:roads,id',
            'pipe_type'               => 'required|in:wastewater,rainwater',
            'code'                    => "required|string|max:50|unique:water_pipes,code,{$id}",
            'name'                    => 'nullable|string|max:255',
            'start_manhole_id'        => 'nullable|integer',
            'end_manhole_id'          => 'nullable|integer',
            'cross_section'           => 'required|in:circular,box,open_channel',
            'diameter'                => 'nullable|integer',
            'width'                   => 'nullable|integer',
            'height'                  => 'nullable|integer',
            'material'                => 'nullable|string|max:50',
            'invert_start_elevation'  => 'nullable|numeric',
            'invert_end_elevation'    => 'nullable|numeric',
            'slope'                   => 'nullable|numeric',
            'status'                  => 'required|in:active,clogged,damaged,maintenance',
            'geom'                    => 'nullable|string',
        ]);

        $geomGeoJson = $validated['geom'] ?? null;
        unset($validated['geom']);

        $pipe->update($validated);

        if (!empty($geomGeoJson)) {
            DB::statement("
                UPDATE water_pipes 
                SET geom = ST_Multi(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)) 
                WHERE id = ?
            ", [$geomGeoJson, $pipe->id]);
        }

        // 🌟 Đồng bộ cốt đáy
        $this->syncManholeElevation(
            $validated['pipe_type'], 
            $validated['start_manhole_id'] ?? null, 
            $validated['invert_start_elevation'] ?? null
        );
        $this->syncManholeElevation(
            $validated['pipe_type'], 
            $validated['end_manhole_id'] ?? null, 
            $validated['invert_end_elevation'] ?? null
        );

        return redirect()->back()->with('success', 'Cập nhật tuyến cống thành công!');
    }

    public function destroy($id)
    {
        WaterPipe::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Đã xóa đoạn cống!');
    }

    /**
     * Cập nhật cốt đáy vào bảng hố ga tương ứng nếu hố ga đó chưa có cốt đáy
     */
    private function syncManholeElevation($pipeType, $manholeId, $elevation)
    {
        if (!$manholeId || $elevation === null) return;

        $table = ($pipeType === 'wastewater') ? 'wastewater_manholes' : 'rainwater_manholes';

        // Chỉ cập nhật nếu cốt đáy hiện tại đang null hoặc = 0
        DB::table($table)
            ->where('id', $manholeId)
            ->where(function ($q) {
                $q->whereNull('elevation')->orWhere('elevation', 0);
            })
            ->update(['elevation' => $elevation]);
    }
}