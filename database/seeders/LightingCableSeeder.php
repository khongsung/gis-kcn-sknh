<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\IndustrialZone;
use App\Models\LightingCabinet;
use App\Models\Road;

class LightingCableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Lấy thông tin KCN Song Khê - Nội Hoàng
        $zoneSknh = IndustrialZone::where('code', 'SK_NH')->first();

        if (!$zoneSknh) {
            $this->command->warn('⚠️ Chưa tìm thấy KCN Song Khê - Nội Hoàng trong bảng industrial_zones!');
            return;
        }

        // 2. Lấy các Tủ điện tương ứng tại KCN Song Khê - Nội Hoàng
        $cabinet01 = LightingCabinet::where('code', 'TDK-SKNH-01')->first();
        $cabinet02 = LightingCabinet::where('code', 'TDK-SKNH-02')->first();
        $cabinet03 = LightingCabinet::where('code', 'TDK-SKNH-03')->first();

        // 3. Lấy tuyến đường liên quan (nếu có)
        $roadSknh = Road::where('zone_id', $zoneSknh->id)->first();

        // Danh sách các tuyến cáp mẫu dọc theo trục đường KCN Song Khê - Nội Hoàng
        $cables = [
            // =========================================================================
            // Lộ 1: Xuất phát từ Tủ TDK-SKNH-01 chạy dọc Trục chính D1 (Cổng KCN)
            // =========================================================================
            [
                'zone_id'           => $zoneSknh->id,
                'cabinet_id'        => $cabinet01?->id,
                'road_id'           => $roadSknh?->id,
                'code'              => 'CAP-CS-SKNH-L01',
                'name'              => 'Lộ cáp chiếu sáng Trục chính D1 (Nhánh Bắc)',
                'cable_spec'        => 'Cu/XLPE/PVC/DSTA/PVC 4x25mm2',
                'installation_type' => 'underground',
                'depth_m'           => 0.8,
                'conduit_type'      => 'Ống nhựa xoắn HDPE Ba An D85/65',
                'status'            => 'normal',
                // Tọa độ mốc tim tuyến cáp ngầm [Kinh độ, Vĩ độ]
                'coords'            => [
                    [106.177420, 21.246850], // Điểm đấu nối tại Tủ TDK-SKNH-01
                    [106.177800, 21.246980],
                    [106.178350, 21.247150],
                    [106.178900, 21.247320],
                    [106.179450, 21.247500],
                ]
            ],
            [
                'zone_id'           => $zoneSknh->id,
                'cabinet_id'        => $cabinet01?->id,
                'road_id'           => $roadSknh?->id,
                'code'              => 'CAP-CS-SKNH-L02',
                'name'              => 'Lộ cáp chiếu sáng Cổng KCN hướng QL37',
                'cable_spec'        => 'Cu/XLPE/PVC/DSTA/PVC 4x16mm2',
                'installation_type' => 'underground',
                'depth_m'           => 0.7,
                'conduit_type'      => 'Ống nhựa xoắn HDPE D65/50',
                'status'            => 'normal',
                'coords'            => [
                    [106.177420, 21.246850], // Điểm đấu nối tại Tủ TDK-SKNH-01
                    [106.176950, 21.246680],
                    [106.176400, 21.246450],
                    [106.175850, 21.246200],
                ]
            ],

            // =========================================================================
            // Lộ 2: Xuất phát từ Tủ TDK-SKNH-02 chạy quanh Khu Trạm xử lý nước thải (N2)
            // =========================================================================
            [
                'zone_id'           => $zoneSknh->id,
                'cabinet_id'        => $cabinet02?->id,
                'road_id'           => $roadSknh?->id,
                'code'              => 'CAP-CS-SKNH-L03',
                'name'              => 'Lộ cáp chiếu sáng Đường nội bộ Trạm XLNT',
                'cable_spec'        => 'Cu/XLPE/PVC/DSTA/PVC 4x16mm2',
                'installation_type' => 'underground',
                'depth_m'           => 0.7,
                'conduit_type'      => 'Ống nhựa xoắn HDPE D65/50',
                'status'            => 'normal',
                'coords'            => [
                    [106.179850, 21.248100], // Điểm đấu nối tại Tủ TDK-SKNH-02
                    [106.180250, 21.248250],
                    [106.180800, 21.248450],
                    [106.181200, 21.248600],
                    [106.181650, 21.248800],
                ]
            ],

            // =========================================================================
            // Lộ 3: Xuất phát từ Tủ TDK-SKNH-03 chạy dọc Tuyến D3 phía Nam
            // =========================================================================
            [
                'zone_id'           => $zoneSknh->id,
                'cabinet_id'        => $cabinet03?->id,
                'road_id'           => null,
                'code'              => 'CAP-CS-SKNH-L04',
                'name'              => 'Lộ cáp chiếu sáng Trục D3 (Phân kỳ mở rộng phía Nam)',
                'cable_spec'        => 'Cu/XLPE/PVC/DSTA/PVC 4x25mm2',
                'installation_type' => 'underground',
                'depth_m'           => 0.8,
                'conduit_type'      => 'Ống nhựa xoắn HDPE D85/65',
                'status'            => 'repairing', // Đang kéo bổ sung
                'coords'            => [
                    [106.178300, 21.244950], // Điểm đấu nối tại Tủ TDK-SKNH-03
                    [106.178700, 21.245100],
                    [106.179200, 21.245250],
                    [106.179750, 21.245450],
                    [106.180300, 21.245650],
                ]
            ],
        ];

        foreach ($cables as $item) {
            // Kiểm tra tránh chèn trùng theo mã tuyến
            $exists = DB::table('lighting_cables')->where('code', $item['code'])->exists();
            if ($exists) {
                continue;
            }

            // Đóng gói GeoJSON LineString
            $geojson = json_encode([
                'type'        => 'LineString',
                'coordinates' => $item['coords'],
            ]);

            // Dùng PostGIS tự tính toán độ dài mét thực tế (length_m)
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
                'zone_id'           => $item['zone_id'],
                'cabinet_id'        => $item['cabinet_id'],
                'road_id'           => $item['road_id'],
                'code'              => $item['code'],
                'name'              => $item['name'],
                'cable_spec'        => $item['cable_spec'],
                'installation_type' => $item['installation_type'],
                'depth_m'           => $item['depth_m'],
                'conduit_type'      => $item['conduit_type'],
                'status'            => $item['status'],
                'geom'              => $geojson,
            ]);
        }

        $this->command->info('✅ Đã nạp thành công các tuyến cáp chiếu sáng mẫu KCN Song Khê - Nội Hoàng!');
    }
}