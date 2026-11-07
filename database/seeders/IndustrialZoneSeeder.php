<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IndustrialZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('industrial_zones')->insertOrIgnore([
            [
                'id'           => 1,
                'code'         => 'SK_NH',
                'name'         => 'KCN Song Khê - Nội Hoàng',
                'province'     => 'Bắc Giang',
                'address'      => 'Xã Tiền Phong, Yên Dũng & Xã Song Khê, TP. Bắc Giang',
                'center_lat'   => 21.246624,
                'center_lng'   => 106.178020,
                'default_zoom' => 16,
                'total_area'   => 1587000.00, // ~158.7 ha
                'description'  => 'KCN Song Khê - Nội Hoàng nằm cạnh QL1A',
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => 2,
                'code'         => 'DT',
                'name'         => 'KCN Đình Trám',
                'province'     => 'Bắc Giang',
                'address'      => 'Thị xã Việt Yên, tỉnh Bắc Giang',
                'center_lat'   => 21.235500,
                'center_lng'   => 106.126800,
                'default_zoom' => 16,
                'total_area'   => 1270000.00, // ~127 ha
                'description'  => 'KCN Đình Trám tại nút giao QL1A và QL37',
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]
        ]);
    }
}
