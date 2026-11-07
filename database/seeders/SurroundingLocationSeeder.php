<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\IndustrialZone;

class SurroundingLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $zoneSknh = IndustrialZone::where('code', 'SK_NH')->first();

        $locations = [
            // 1. Phía Bắc: Khu dân cư xã Song Khê
            [
                'zone_id'     => $zoneSknh?->id,
                'code'        => 'KDC-SONG-KHE',
                'name'        => 'Khu dân cư xã Song Khê',
                'category'    => 'residential',
                'bg_color'    => '#047857', // Xanh lục đậm
                'text_color'  => '#ffffff',
                'icon'        => '🏘️',
                'description' => 'Khu dân cư hiện hữu tiếp giáp phía Bắc KCN',
                'lat'         => 21.254200,
                'lng'         => 106.176500,
            ],

            // 2. Phía Đông / Đông Nam: Nút giao QL37 & Cao tốc Hà Nội - Bắc Giang
            [
                'zone_id'     => $zoneSknh?->id,
                'code'        => 'NUT-GIAO-QL37',
                'name'        => 'Nút giao QL37 & Cao tốc HN - BG',
                'category'    => 'transport',
                'bg_color'    => '#b45309', // Hổ phách / Cam đậm
                'text_color'  => '#ffffff',
                'icon'        => '🛣️',
                'description' => 'Trục kết nối giao thông đối ngoại huyết mạch',
                'lat'         => 21.244100,
                'lng'         => 106.171200,
            ],

            // 3. Phía Nam: Khu dân cư xã Nội Hoàng & KCN Vân Trung phụ cận
            [
                'zone_id'     => $zoneSknh?->id,
                'code'        => 'KDC-NOI-HOANG',
                'name'        => 'Khu dân cư xã Nội Hoàng',
                'category'    => 'residential',
                'bg_color'    => '#059669', // Xanh lục
                'text_color'  => '#ffffff',
                'icon'        => '🏘️',
                'description' => 'Tiếp giáp phân kỳ mở rộng phía Nam KCN',
                'lat'         => 21.238500,
                'lng'         => 106.182400,
            ],

            // 4. Phía Đông: Cụm Cảng cạn & Dịch vụ Logistics phụ cận
            [
                'zone_id'     => $zoneSknh?->id,
                'code'        => 'LOGISTICS-SKNH',
                'name'        => 'Khu Logistics & Kho bãi Phía Đông',
                'category'    => 'logistics',
                'bg_color'    => '#1e293b', // Xanh đen than đá (Slate)
                'text_color'  => '#38bdf8', // Chữ xanh sáng nổi bật
                'icon'        => '🚚',
                'description' => 'Cụm kho vận trung chuyển hàng hóa công nghiệp',
                'lat'         => 21.249800,
                'lng'         => 106.187200,
            ],

            // 5. Phía Tây: Hành lang Nông nghiệp sinh thái & Đê Sông Thương
            [
                'zone_id'     => $zoneSknh?->id,
                'code'        => 'DE-SONG-THUONG',
                'name'        => 'Vùng đệm hành lang Sông Thương',
                'category'    => 'ecological',
                'bg_color'    => '#0284c7', // Xanh nước biển
                'text_color'  => '#ffffff',
                'icon'        => '🌊',
                'description' => 'Vùng đệm sinh thái và đê phòng chống lũ',
                'lat'         => 21.248200,
                'lng'         => 106.166800,
            ],
        ];

        foreach ($locations as $item) {
            $exists = DB::table('surrounding_locations')->where('code', $item['code'])->exists();
            if ($exists) {
                continue;
            }

            DB::statement("
                INSERT INTO surrounding_locations (
                    zone_id, code, name, category, bg_color, text_color, icon, description,
                    is_active, geom, created_at, updated_at
                ) VALUES (
                    :zone_id, :code, :name, :category, :bg_color, :text_color, :icon, :description,
                    true, ST_SetSRID(ST_Point(:lng, :lat), 4326), NOW(), NOW()
                )
            ", [
                'zone_id'     => $item['zone_id'],
                'code'        => $item['code'],
                'name'        => $item['name'],
                'category'    => $item['category'],
                'bg_color'    => $item['bg_color'],
                'text_color'  => $item['text_color'],
                'icon'        => $item['icon'],
                'description' => $item['description'],
                'lng'         => $item['lng'],
                'lat'         => $item['lat'],
            ]);
        }

        $this->command->info('✅ Đã nạp thành công 5 nhãn vùng lân cận KCN Song Khê - Nội Hoàng!');
    }
}