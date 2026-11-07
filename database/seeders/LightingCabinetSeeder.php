<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\IndustrialZone;
use App\Models\Road;

class LightingCabinetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lấy ID của 2 KCN
        $zoneSknh = IndustrialZone::where('code', 'SK_NH')->first();
        $zoneDt   = IndustrialZone::where('code', 'DT')->first();

        // Nếu chưa có bảng KCN thì dừng tránh lỗi
        if (!$zoneSknh && !$zoneDt) {
            $this->command->warn('⚠️ Chưa có dữ liệu trong bảng industrial_zones. Hãy chạy IndustrialZoneSeeder trước!');
            return;
        }

        // Lấy ID một số tuyến đường nếu có
        $roadSknh = Road::where('zone_id', $zoneSknh?->id)->first();
        $roadDt   = Road::where('zone_id', $zoneDt?->id)->first();

        $cabinets = [
            // ==========================================
            // 1. KCN SONG KHÊ - NỘI HOÀNG (Bắc Giang)
            // ==========================================
            [
                'zone_id'           => $zoneSknh?->id ?? 1,
                'road_id'           => $roadSknh?->id,
                'code'              => 'TDK-SKNH-01',
                'name'              => 'Tủ ĐK Chiếu Sáng Trục D1 (Cổng KCN)',
                'capacity_kva'      => 30.0,
                'control_type'      => 'timer',
                'power_source'      => 'TBA Song Khê 1 (400kVA)',
                'channels_count'    => 3,
                'status'            => 'active',
                'installation_date' => '2023-04-15',
                'notes'             => 'Cấp nguồn cho tuyến đèn đường trục D1 và cổng chào KCN',
                'lat'               => 21.246850,
                'lng'               => 106.177420,
            ],
            [
                'zone_id'           => $zoneSknh?->id ?? 1,
                'road_id'           => $roadSknh?->id,
                'code'              => 'TDK-SKNH-02',
                'name'              => 'Tủ ĐK Chiếu Sáng Trục N2 (Khu Nhà máy nước thải)',
                'capacity_kva'      => 25.0,
                'control_type'      => 'photocell',
                'power_source'      => 'TBA Trạm Xử Lý Nước Thải',
                'channels_count'    => 2,
                'status'            => 'active',
                'installation_date' => '2023-06-20',
                'notes'             => 'Tích hợp cảm biến quang và timer đóng cắt 18h - 05h',
                'lat'               => 21.248100,
                'lng'               => 106.179850,
            ],
            [
                'zone_id'           => $zoneSknh?->id ?? 1,
                'road_id'           => null,
                'code'              => 'TDK-SKNH-03',
                'name'              => 'Tủ ĐK Chiếu Sáng Tuyến D3 Phía Nam',
                'capacity_kva'      => 20.0,
                'control_type'      => 'timer',
                'power_source'      => 'TBA Hạ Tầng Nội Hoàng',
                'channels_count'    => 2,
                'status'            => 'maintenance',
                'installation_date' => '2024-01-10',
                'notes'             => 'Đang kiểm tra rơ le thời gian và chống sét van',
                'lat'               => 21.244950,
                'lng'               => 106.178300,
            ],

            // ==========================================
            // 2. KCN ĐÌNH TRÁM (Việt Yên, Bắc Giang)
            // ==========================================
            [
                'zone_id'           => $zoneDt?->id ?? 2,
                'road_id'           => $roadDt?->id,
                'code'              => 'TDK-DT-01',
                'name'              => 'Tủ ĐK Chiếu Sáng Ngã Tư QL37 - Đình Trám',
                'capacity_kva'      => 40.0,
                'control_type'      => 'smart_iot',
                'power_source'      => 'TBA Trung Tâm Điều Hành Đình Trám',
                'channels_count'    => 4,
                'status'            => 'active',
                'installation_date' => '2022-11-05',
                'notes'             => 'Module điều khiển từ xa qua mạng 4G/IoT',
                'lat'               => 21.235800,
                'lng'               => 106.126500,
            ],
            [
                'zone_id'           => $zoneDt?->id ?? 2,
                'road_id'           => $roadDt?->id,
                'code'              => 'TDK-DT-02',
                'name'              => 'Tủ ĐK Chiếu Sáng Dải Phân Cách Trục Chính',
                'capacity_kva'      => 35.0,
                'control_type'      => 'timer',
                'power_source'      => 'TBA Đình Trám 2',
                'channels_count'    => 3,
                'status'            => 'active',
                'installation_date' => '2023-08-12',
                'notes'             => 'Hẹn giờ cắt giảm 50% công suất sau 23h đêm để tiết kiệm điện',
                'lat'               => 21.234600,
                'lng'               => 106.128200,
            ]
        ];

        foreach ($cabinets as $item) {
            // Kiểm tra tránh chèn trùng theo code
            $exists = DB::table('lighting_cabinets')->where('code', $item['code'])->exists();
            if ($exists) {
                continue;
            }

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
                'zone_id'           => $item['zone_id'],
                'road_id'           => $item['road_id'],
                'code'              => $item['code'],
                'name'              => $item['name'],
                'capacity_kva'      => $item['capacity_kva'],
                'control_type'      => $item['control_type'],
                'power_source'      => $item['power_source'],
                'channels_count'    => $item['channels_count'],
                'status'            => $item['status'],
                'installation_date' => $item['installation_date'],
                'notes'             => $item['notes'],
                'lng'               => $item['lng'],
                'lat'               => $item['lat'],
            ]);
        }

        $this->command->info('✅ Đã nạp thành công 5 bản ghi mẫu Tủ điện chiếu sáng PostGIS!');
    }
}