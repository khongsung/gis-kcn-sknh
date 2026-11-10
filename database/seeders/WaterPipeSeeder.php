<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WaterPipeSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Lấy zone_id của KCN Song Khê - Nội Hoàng
        $zone = DB::table('industrial_zones')
            ->where('code', 'SK_NH')
            ->orWhere('name', 'LIKE', '%Song Khê%')
            ->first() ?? DB::table('industrial_zones')->first();

        $zoneId = $zone ? $zone->id : null;

        // 2. Lấy road_id mẫu (nếu bảng roads đã có dữ liệu)
        $road = DB::table('roads')->first();
        $roadId = $road ? $road->id : null;

        // Xóa sạch dữ liệu cũ trước khi nạp
        DB::table('water_pipes')->truncate();

        // 3. Tạo danh sách các đoạn cống mẫu
        $pipes = [
            // ==========================================
            // A. HỆ THỐNG THU GOM NƯỚC THẢI (wastewater)
            // ==========================================
			[
			    'zone_id'                 => $zoneId,
			    'road_id'                 => $roadId,
			    'pipe_type'               => 'wastewater',
			    'code'                    => 'NT-D1-01',
			    'name'                    => 'Tuyến cống thu gom nước thải D300 trục D1',
			    'start_manhole_code'      => 'HG-NT-01',
			    'end_manhole_code'        => 'HG-NT-02',
			    'cross_section'           => 'circular',
			    'diameter'                => 300,
			    'width'                   => null,
			    'height'                  => null,
			    'material'                => 'HDPE 2 vách',
			    'invert_start_elevation'  => 4.850,
			    'invert_end_elevation'    => 4.620,
			    'slope'                   => 0.350,
			    'status'                  => 'active',
			    'last_inspected_at'       => '2026-03-15',
			    'description'             => 'Tuyến cống tự chảy dẫn về trạm bơm nâng cốt số 1',
			    // 🌟 Sửa: Chỉ dùng 2 cặp ngoặc tròn
			    'wkt'                     => 'MULTILINESTRING((106.17620 21.24750, 106.17710 21.24780))'
			],
			[
			    'zone_id'                 => $zoneId,
			    'road_id'                 => $roadId,
			    'pipe_type'               => 'wastewater',
			    'code'                    => 'NT-TRUC-02',
			    'name'                    => 'Tuyến cống thu gom chính D400 có nhánh rẽ đấu nối xưởng',
			    'start_manhole_code'      => 'HG-NT-02',
			    'end_manhole_code'        => 'HG-NT-03',
			    'cross_section'           => 'circular',
			    'diameter'                => 400,
			    'width'                   => null,
			    'height'                  => null,
			    'material'                => 'HDPE 2 vách',
			    'invert_start_elevation'  => 4.620,
			    'invert_end_elevation'    => 4.310,
			    'slope'                   => 0.400,
			    'status'                  => 'active',
			    'last_inspected_at'       => '2026-04-10',
			    'description'             => 'Tuyến cống có nhánh chữ Y đón nước thải từ cụm nhà xưởng lô F',
			    // 🌟 Sửa: Nhánh rẽ phân cách bởi dấu phẩy giữa 2 đường con: ((line1), (line2))
			    'wkt'                     => 'MULTILINESTRING((106.17710 21.24780, 106.17820 21.24820), (106.17780 21.24880, 106.17760 21.24800))'
			],

			// ==========================================
			// B. HỆ THỐNG THOÁT NƯỚC MƯA (rainwater)
			// ==========================================
			[
			    'zone_id'                 => $zoneId,
			    'road_id'                 => $roadId,
			    'pipe_type'               => 'rainwater',
			    'code'                    => 'NM-TRUC-01',
			    'name'                    => 'Tuyến cống hộp thoát nước mưa chính B1200xH1500',
			    'start_manhole_code'      => 'HG-NM-01',
			    'end_manhole_code'        => 'HG-NM-02',
			    'cross_section'           => 'box',
			    'diameter'                => null,
			    'width'                   => 1200,
			    'height'                  => 1500,
			    'material'                => 'Bê tông cốt thép',
			    'invert_start_elevation'  => 5.200,
			    'invert_end_elevation'    => 4.900,
			    'slope'                   => 0.250,
			    'status'                  => 'active',
			    'last_inspected_at'       => '2026-02-20',
			    'description'             => 'Cống hộp thoát nước mưa dọc dải cây xanh trục chính',
			    // 🌟 Sửa: Tuyến 3 điểm uốn lượn
			    'wkt'                     => 'MULTILINESTRING((106.17550 21.24680, 106.17680 21.24730, 106.17800 21.24770))'
			],
			[
			    'zone_id'                 => $zoneId,
			    'road_id'                 => $roadId,
			    'pipe_type'               => 'rainwater',
			    'code'                    => 'NM-D800-02',
			    'name'                    => 'Tuyến cống tròn bê tông D800 thoát ra kênh hở',
			    'start_manhole_code'      => 'HG-NM-02',
			    'end_manhole_code'        => 'CUA-XA-01',
			    'cross_section'           => 'circular',
			    'diameter'                => 800,
			    'width'                   => null,
			    'height'                  => null,
			    'material'                => 'Bê tông cốt thép',
			    'invert_start_elevation'  => 4.900,
			    'invert_end_elevation'    => 4.550,
			    'slope'                   => 0.300,
			    'status'                  => 'active',
			    'last_inspected_at'       => '2026-01-18',
			    'description'             => 'Đoạn xả tràn nước mưa ra kênh thoát nước ngoại vi',
			    // 🌟 Sửa: Tuyến thẳng ra cửa xả
			    'wkt'                     => 'MULTILINESTRING((106.17800 21.24770, 106.17930 21.24810))'
			],
        ];

        // 4. Insert vào DB kèm ST_GeomFromText để nạp MultiLineString PostGIS
        foreach ($pipes as $pipe) {
            $wkt = $pipe['wkt'];
            unset($pipe['wkt']);

            $pipe['created_at'] = now();
            $pipe['updated_at'] = now();

            $id = DB::table('water_pipes')->insertGetId($pipe);

            // Cập nhật tọa độ không gian PostGIS chuẩn SRID 4326
            DB::statement("
                UPDATE water_pipes 
                SET geom = ST_SetSRID(ST_GeomFromText(?), 4326) 
                WHERE id = ?
            ", [$wkt, $id]);
        }
    }
}