<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\IndustrialZone;
use App\Models\LightingCabinet;
use App\Models\Road;

class LightingPoleSeeder extends Seeder
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

        // 2. Lấy các Tủ điện cấp nguồn tại KCN Song Khê - Nội Hoàng
        $cab01 = LightingCabinet::where('code', 'TDK-SKNH-01')->first();
        $cab02 = LightingCabinet::where('code', 'TDK-SKNH-02')->first();
        $cab03 = LightingCabinet::where('code', 'TDK-SKNH-03')->first();

        // 3. Lấy tuyến đường liên kết
        $roadSknh = Road::where('zone_id', $zoneSknh->id)->first();

        $poles = [
            // =========================================================================
            // TUYẾN 1: Dọc Trục chính D1 (Nhánh Bắc) - Nguồn từ Tủ TDK-SKNH-01
            // Cột thép mạ kẽm bát giác 8m, bóng LED 150W tiết kiệm điện
            // =========================================================================
            [
                'zone_id'           => $zoneSknh->id,
                'cabinet_id'        => $cab01?->id,
                'road_id'           => $roadSknh?->id,
                'code'              => 'C-CS-D1-01',
                'height_m'          => 8.0,
                'pole_material'     => 'Thép mạ kẽm',
                'arm_type'          => 'Cần đơn',
                'lamp_type'         => 'LED',
                'wattage'           => 150,
                'status'            => 'working',
                'installation_date' => '2023-04-15',
                'notes'             => 'Cột đầu tuyến cạnh tủ điều khiển TDK-01',
                'lat'               => 21.246870,
                'lng'               => 106.177450,
            ],
            [
                'zone_id'           => $zoneSknh->id,
                'cabinet_id'        => $cab01?->id,
                'road_id'           => $roadSknh?->id,
                'code'              => 'C-CS-D1-02',
                'height_m'          => 8.0,
                'pole_material'     => 'Thép mạ kẽm',
                'arm_type'          => 'Cần đơn',
                'lamp_type'         => 'LED',
                'wattage'           => 150,
                'status'            => 'working',
                'installation_date' => '2023-04-15',
                'notes'             => 'Vỉa hè phía Đông trục D1',
                'lat'               => 21.247010,
                'lng'               => 106.177820,
            ],
            [
                'zone_id'           => $zoneSknh->id,
                'cabinet_id'        => $cab01?->id,
                'road_id'           => $roadSknh?->id,
                'code'              => 'C-CS-D1-03',
                'height_m'          => 8.0,
                'pole_material'     => 'Thép mạ kẽm',
                'arm_type'          => 'Cần đơn',
                'lamp_type'         => 'LED',
                'wattage'           => 150,
                'status'            => 'working',
                'installation_date' => '2023-04-15',
                'notes'             => 'Trước cổng nhà xưởng Lô CN-01',
                'lat'               => 21.247180,
                'lng'               => 106.178370,
            ],
            [
                'zone_id'           => $zoneSknh->id,
                'cabinet_id'        => $cab01?->id,
                'road_id'           => $roadSknh?->id,
                'code'              => 'C-CS-D1-04',
                'height_m'          => 8.0,
                'pole_material'     => 'Thép mạ kẽm',
                'arm_type'          => 'Cần đơn',
                'lamp_type'         => 'LED',
                'wattage'           => 150,
                'status'            => 'working',
                'installation_date' => '2023-04-15',
                'notes'             => 'Khu vực ngã ba đường D1 giao N1',
                'lat'               => 21.247350,
                'lng'               => 106.178920,
            ],
            [
                'zone_id'           => $zoneSknh->id,
                'cabinet_id'        => $cab01?->id,
                'road_id'           => $roadSknh?->id,
                'code'              => 'C-CS-D1-05',
                'height_m'          => 8.0,
                'pole_material'     => 'Thép mạ kẽm',
                'arm_type'          => 'Cần đôi',
                'lamp_type'         => 'LED',
                'wattage'           => 200,
                'status'            => 'working',
                'installation_date' => '2023-04-15',
                'notes'             => 'Cần đôi chiếu sáng dải phân cách cuối trục D1',
                'lat'               => 21.247520,
                'lng'               => 106.179470,
            ],

            // =========================================================================
            // TUYẾN 2: Cổng KCN hướng QL37 - Nguồn từ Tủ TDK-SKNH-01
            // =========================================================================
            [
                'zone_id'           => $zoneSknh->id,
                'cabinet_id'        => $cab01?->id,
                'road_id'           => $roadSknh?->id,
                'code'              => 'C-CS-GATE-01',
                'height_m'          => 10.0,
                'pole_material'     => 'Thép mạ kẽm',
                'arm_type'          => 'Cần đôi',
                'lamp_type'         => 'LED',
                'wattage'           => 200,
                'status'            => 'working',
                'installation_date' => '2023-04-20',
                'notes'             => 'Trụ cổng chào chính KCN',
                'lat'               => 21.246700,
                'lng'               => 106.176980,
            ],
            [
                'zone_id'           => $zoneSknh->id,
                'cabinet_id'        => $cab01?->id,
                'road_id'           => $roadSknh?->id,
                'code'              => 'C-CS-GATE-02',
                'height_m'          => 8.0,
                'pole_material'     => 'Thép mạ kẽm',
                'arm_type'          => 'Cần đơn',
                'lamp_type'         => 'LED',
                'wattage'           => 120,
                'status'            => 'working',
                'installation_date' => '2023-04-20',
                'notes'             => 'Đoạn nối ra Quốc lộ 37',
                'lat'               => 21.246470,
                'lng'               => 106.176420,
            ],
            [
                'zone_id'           => $zoneSknh->id,
                'cabinet_id'        => $cab01?->id,
                'road_id'           => $roadSknh?->id,
                'code'              => 'C-CS-GATE-03',
                'height_m'          => 8.0,
                'pole_material'     => 'Thép mạ kẽm',
                'arm_type'          => 'Cần đơn',
                'lamp_type'         => 'LED',
                'wattage'           => 120,
                'status'            => 'broken', // 🌟 Giả lập 1 cột hỏng để test hiển thị trạng thái đỏ trên Admin/Map
                'installation_date' => '2023-04-20',
                'notes'             => 'Cháy nguồn chấn lưu LED, đang chờ linh kiện thay thế',
                'lat'               => 21.246220,
                'lng'               => 106.175870,
            ],

            // =========================================================================
            // TUYẾN 3: Tuyến Trạm Xử Lý Nước Thải - Nguồn từ Tủ TDK-SKNH-02
            // =========================================================================
            [
                'zone_id'           => $zoneSknh->id,
                'cabinet_id'        => $cab02?->id,
                'road_id'           => $roadSknh?->id,
                'code'              => 'C-CS-XLNT-01',
                'height_m'          => 8.0,
                'pole_material'     => 'Bê tông ly tâm',
                'arm_type'          => 'Cần đơn',
                'lamp_type'         => 'Sodium Cao Áp',
                'wattage'           => 250,
                'status'            => 'working',
                'installation_date' => '2023-06-25',
                'notes'             => 'Cổng vào khu xử lý nước thải tập trung',
                'lat'               => 21.248270,
                'lng'               => 106.180280,
            ],
            [
                'zone_id'           => $zoneSknh->id,
                'cabinet_id'        => $cab02?->id,
                'road_id'           => $roadSknh?->id,
                'code'              => 'C-CS-XLNT-02',
                'height_m'          => 8.0,
                'pole_material'     => 'Bê tông ly tâm',
                'arm_type'          => 'Cần đơn',
                'lamp_type'         => 'Sodium Cao Áp',
                'wattage'           => 250,
                'status'            => 'working',
                'installation_date' => '2023-06-25',
                'notes'             => 'Khu vực cụm bể lắng và trạm quan trắc tự động',
                'lat'               => 21.248470,
                'lng'               => 106.180830,
            ],
            [
                'zone_id'           => $zoneSknh->id,
                'cabinet_id'        => $cab02?->id,
                'road_id'           => $roadSknh?->id,
                'code'              => 'C-CS-XLNT-03',
                'height_m'          => 8.0,
                'pole_material'     => 'Bê tông ly tâm',
                'arm_type'          => 'Cần đơn',
                'lamp_type'         => 'LED',
                'wattage'           => 150,
                'status'            => 'maintenance',
                'installation_date' => '2023-06-25',
                'notes'             => 'Đang cân chỉnh rơ le bảo vệ',
                'lat'               => 21.248620,
                'lng'               => 106.181220,
            ],

            // =========================================================================
            // TUYẾN 4: Phân kỳ mở rộng phía Nam (Trục D3) - Nguồn từ Tủ TDK-SKNH-03
            // =========================================================================
            [
                'zone_id'           => $zoneSknh->id,
                'cabinet_id'        => $cab03?->id,
                'road_id'           => null,
                'code'              => 'C-CS-D3-01',
                'height_m'          => 9.0,
                'pole_material'     => 'Thép mạ kẽm',
                'arm_type'          => 'Cần đơn',
                'lamp_type'         => 'LED',
                'wattage'           => 150,
                'status'            => 'working',
                'installation_date' => '2024-01-15',
                'notes'             => 'Cột thép bát giác mạ kẽm liền cần',
                'lat'               => 21.245120,
                'lng'               => 106.178730,
            ],
            [
                'zone_id'           => $zoneSknh->id,
                'cabinet_id'        => $cab03?->id,
                'road_id'           => null,
                'code'              => 'C-CS-D3-02',
                'height_m'          => 9.0,
                'pole_material'     => 'Thép mạ kẽm',
                'arm_type'          => 'Cần đơn',
                'lamp_type'         => 'LED',
                'wattage'           => 150,
                'status'            => 'working',
                'installation_date' => '2024-01-15',
                'notes'             => 'Đoạn gần hồ điều hòa phân kỳ Nam',
                'lat'               => 21.245270,
                'lng'               => 106.179230,
            ],
            [
                'zone_id'           => $zoneSknh->id,
                'cabinet_id'        => $cab03?->id,
                'road_id'           => null,
                'code'              => 'C-CS-D3-03',
                'height_m'          => 9.0,
                'pole_material'     => 'Thép mạ kẽm',
                'arm_type'          => 'Cần đơn',
                'lamp_type'         => 'LED',
                'wattage'           => 150,
                'status'            => 'working',
                'installation_date' => '2024-01-15',
                'notes'             => 'Cột cuối lộ cáp L04',
                'lat'               => 21.245470,
                'lng'               => 106.179780,
            ],
        ];

        foreach ($poles as $item) {
            $exists = DB::table('lighting_poles')->where('code', $item['code'])->exists();
            if ($exists) {
                continue;
            }

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
                'zone_id'           => $item['zone_id'],
                'cabinet_id'        => $item['cabinet_id'],
                'road_id'           => $item['road_id'],
                'code'              => $item['code'],
                'height_m'          => $item['height_m'],
                'pole_material'     => $item['pole_material'],
                'arm_type'          => $item['arm_type'],
                'lamp_type'         => $item['lamp_type'],
                'wattage'           => $item['wattage'],
                'status'            => $item['status'],
                'installation_date' => $item['installation_date'],
                'notes'             => $item['notes'],
                'lng'               => $item['lng'],
                'lat'               => $item['lat'],
            ]);
        }

        $this->command->info('✅ Đã nạp thành công 14 Cột đèn chiếu sáng mẫu KCN Song Khê - Nội Hoàng!');
    }
}