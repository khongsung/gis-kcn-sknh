<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanyLand;
use App\Models\IndustrialZone;
use App\Models\Plot;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanyLandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Lấy dữ liệu ID hiện có từ DB để đảm bảo toàn vẹn khóa ngoại
        $zone = IndustrialZone::first() ?? DB::table('industrial_zones')->first();
        $zoneId = $zone?->id ?? 1;

        $companies = Company::pluck('id')->toArray();
        $plots = Plot::pluck('id')->toArray();

        // Fallback nếu database chưa có company hoặc plot
        $c1 = $companies[0] ?? 1;
        $c2 = $companies[1] ?? $c1;
        $c3 = $companies[2] ?? $c1;
        $c4 = $companies[3] ?? $c1;
        $c5 = $companies[4] ?? $c1;

        $p1 = $plots[0] ?? null;
        $p2 = $plots[1] ?? $p1;
        $p3 = $plots[2] ?? $p1;
        $p4 = $plots[3] ?? $p1;
        $p5 = $plots[4] ?? $p1;

        // 2. Định nghĩa 5 thửa đất doanh nghiệp mẫu (Tọa độ thực tế tại Song Khê - Nội Hoàng)
        $lands = [
            [
                'land_code'             => 'DN-SKNH-01',
                'company_id'            => $c1,
                'plot_id'               => $p1,
                'zone_id'               => $zoneId,
                'certificate_number'    => 'BD 928374',
                'cadastral_book_number' => 'CT 01245',
                'issue_date'            => '2021-06-15',
                'issuing_authority'     => 'UBND tỉnh Bắc Giang',
                'contract_number'       => '12/2021/HĐTĐ-SKNH',
                'contract_date'         => '2021-04-10',
                'lease_start_date'      => '2021-05-01',
                'lease_end_date'        => '2065-12-31',
                'payment_type'          => 'one_time',
                'land_purpose'          => 'Nhà máy sản xuất linh kiện điện tử và phụ tùng ô tô',
                'building_density_limit'=> 70.0,
                'status'                => 'active',
                'is_handed_over'        => true,
                'description'           => 'Đã hoàn công nhà xưởng 2 tầng, trạm biến áp riêng và hệ thống XLNT cục bộ.',
                // Polygon khép kín: [Lng, Lat]
                'coordinates'           => [
                    [106.177200, 21.245800],
                    [106.178800, 21.245800],
                    [106.178800, 21.244600],
                    [106.177200, 21.244600],
                    [106.177200, 21.245800]
                ]
            ],
            [
                'land_code'             => 'DN-SKNH-02',
                'company_id'            => $c2,
                'plot_id'               => $p2,
                'zone_id'               => $zoneId,
                'certificate_number'    => 'BD 928375',
                'cadastral_book_number' => 'CT 01246',
                'issue_date'            => '2022-03-20',
                'issuing_authority'     => 'Sở Tài nguyên và Môi trường tỉnh Bắc Giang',
                'contract_number'       => '08/2022/HĐTĐ-SKNH',
                'contract_date'         => '2022-02-15',
                'lease_start_date'      => '2022-03-01',
                'lease_end_date'        => '2065-12-31',
                'payment_type'          => 'annual',
                'land_purpose'          => 'Sản xuất bao bì màng nhựa sinh học định hình',
                'building_density_limit'=> 65.0,
                'status'                => 'active',
                'is_handed_over'        => true,
                'description'           => 'Đang vận hành dây chuyền thổi màng, kết nối vào hố ga nước thải tuyến D400.',
                'coordinates'           => [
                    [106.179000, 21.245800],
                    [106.180500, 21.245800],
                    [106.180500, 21.244600],
                    [106.179000, 21.244600],
                    [106.179000, 21.245800]
                ]
            ],
            [
                'land_code'             => 'DN-SKNH-03',
                'company_id'            => $c3,
                'plot_id'               => $p3,
                'zone_id'               => $zoneId,
                'certificate_number'    => 'CG 451209',
                'cadastral_book_number' => 'CT 02891',
                'issue_date'            => '2023-09-12',
                'issuing_authority'     => 'UBND tỉnh Bắc Giang',
                'contract_number'       => '23/2023/HĐTĐ-SKNH',
                'contract_date'         => '2023-08-01',
                'lease_start_date'      => '2023-09-01',
                'lease_end_date'        => '2065-12-31',
                'payment_type'          => 'one_time',
                'land_purpose'          => 'Nhà máy gia công cơ khí chính xác và khuôn mẫu dập',
                'building_density_limit'=> 70.0,
                'status'                => 'active',
                'is_handed_over'        => true,
                'description'           => 'Đã nghiệm thu mốc ranh giới, cốt nền xây dựng bàn giao đạt +4.30m.',
                'coordinates'           => [
                    [106.177200, 21.244300],
                    [106.178800, 21.244300],
                    [106.178800, 21.243200],
                    [106.177200, 21.243200],
                    [106.177200, 21.244300]
                ]
            ],
            [
                'land_code'             => 'DN-SKNH-04',
                'company_id'            => $c4,
                'plot_id'               => $p4,
                'zone_id'               => $zoneId,
                'certificate_number'    => 'DA 118833',
                'cadastral_book_number' => 'CT 04112',
                'issue_date'            => '2025-05-18',
                'issuing_authority'     => 'Sở Tài nguyên và Môi trường tỉnh Bắc Giang',
                'contract_number'       => '11/2025/HĐTĐ-SKNH',
                'contract_date'         => '2025-04-20',
                'lease_start_date'      => '2025-05-01',
                'lease_end_date'        => '2065-12-31',
                'payment_type'          => 'annual',
                'land_purpose'          => 'Xây dựng tổ hợp kho bãi logistics và trung tâm phân phối hàng lạnh',
                'building_density_limit'=> 60.0,
                'status'                => 'under_construction',
                'is_handed_over'        => true,
                'description'           => 'Đang thi công ép cọc móng nhà xưởng số 1 và san lấp hoàn thiện mặt bằng.',
                'coordinates'           => [
                    [106.179000, 21.244300],
                    [106.180500, 21.244300],
                    [106.180500, 21.243200],
                    [106.179000, 21.243200],
                    [106.179000, 21.244300]
                ]
            ],
            [
                'land_code'             => 'DN-SKNH-05',
                'company_id'            => $c5,
                'plot_id'               => $p5,
                'zone_id'               => $zoneId,
                'certificate_number'    => null, // Thửa mới ký HĐ, đang làm thủ tục cấp sổ
                'cadastral_book_number' => null,
                'issue_date'            => null,
                'issuing_authority'     => 'UBND tỉnh Bắc Giang',
                'contract_number'       => '03/2026/HĐTĐ-SKNH',
                'contract_date'         => '2026-02-10',
                'lease_start_date'      => '2026-03-01',
                'lease_end_date'        => '2065-12-31',
                'payment_type'          => 'one_time',
                'land_purpose'          => 'Sản xuất tấm pin năng lượng mặt trời công nghệ cao',
                'building_density_limit'=> 70.0,
                'status'                => 'under_construction',
                'is_handed_over'        => true,
                'description'           => 'Đang hoàn thiện hồ sơ xin cấp GCN QSDĐ lần đầu, đã bàn giao mốc giới ngoài thực địa.',
                'coordinates'           => [
                    [106.177200, 21.242900],
                    [106.179200, 21.242900],
                    [106.179200, 21.241700],
                    [106.177200, 21.241700],
                    [106.177200, 21.242900]
                ]
            ]
        ];

        // 3. Thực hiện insert và tính diện tích trắc địa tự động qua PostGIS
        foreach ($lands as $item) {
            $coords = $item['coordinates'];
            unset($item['coordinates']);

            $geoJson = json_encode([
                'type'        => 'Polygon',
                'coordinates' => [$coords]
            ]);

            // Cập nhật hoặc tạo mới theo mã land_code
            $land = CompanyLand::updateOrCreate(
                ['land_code' => $item['land_code']],
                $item
            );

            // Cập nhật Polygon geometry và tự động tính diện tích mét vuông (m2) bằng ST_Area(geom::geography)
            DB::statement("
                UPDATE company_lands 
                SET geom = ST_SetSRID(ST_GeomFromGeoJSON(?), 4326),
                    calculated_area = ROUND(ST_Area(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)::geography)::numeric, 2),
                    leased_area = COALESCE(leased_area, ROUND(ST_Area(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)::geography)::numeric, 2))
                WHERE id = ?
            ", [$geoJson, $geoJson, $geoJson, $land->id]);
        }

        $this->command->info('✅ Đã tạo thành công 5 bản ghi mẫu Đất doanh nghiệp kèm ranh giới PostGIS!');
    }
}