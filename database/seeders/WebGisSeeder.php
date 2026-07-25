<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class WebGisSeeder extends Seeder
{
    public function run()
    {
        // 1. Dọn dẹp dọn dẹp dữ liệu cũ
        DB::table('rainwater_manholes')->delete();
        DB::table('wastewater_manholes')->delete();
        DB::table('recruitment_news')->delete();
        DB::table('companies')->delete();
        DB::table('factories')->delete();
        DB::table('plots')->delete();


        // 3. Tạo Lô đất CN-01 xung quanh tâm 21.246624, 106.178020
        $plotId = DB::table('plots')->insertGetId([
            'code' => 'CN-01',
            'total_area' => 50000.00,
            'description' => 'Lô đất công nghiệp trung tâm KCN Song Khê - Nội Hoàng',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Note: PostGIS nhận tham số theo định dạng (Kinh độ/Lng Vĩ độ/Lat) -> (106.178... 21.246...)
        DB::statement("
            UPDATE plots 
            SET geom = ST_GeomFromText('POLYGON((106.1765 21.2480, 106.1795 21.2480, 106.1795 21.2450, 106.1765 21.2450, 106.1765 21.2480))', 4326)
            WHERE id = {$plotId}
        ");

        // 4. Tạo Nhà xưởng SK-X01 (Đã thuê - Màu đỏ)
        $f1 = DB::table('factories')->insertGetId([
            'plot_id' => $plotId,
            'code' => 'SK-X01',
            'area' => 12000.00,
            'status' => 'rented',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        DB::statement("
            UPDATE factories 
            SET geom = ST_GeomFromText('POLYGON((106.1768 21.2478, 106.1780 21.2478, 106.1780 21.2462, 106.1768 21.2462, 106.1768 21.2478))', 4326)
            WHERE id = {$f1}
        ");

        // 5. Tạo Nhà xưởng SK-X02 (Đang trống - Màu xanh)
        $f2 = DB::table('factories')->insertGetId([
            'plot_id' => $plotId,
            'code' => 'SK-X02',
            'area' => 8500.00,
            'status' => 'available',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        DB::statement("
            UPDATE factories 
            SET geom = ST_GeomFromText('POLYGON((106.1783 21.2478, 106.1792 21.2478, 106.1792 21.2462, 106.1783 21.2462, 106.1783 21.2478))', 4326)
            WHERE id = {$f2}
        ");

        // 6. Tạo Doanh nghiệp thuê xưởng SK-X01
        $companyId = DB::table('companies')->insertGetId([
            'name' => 'Công ty TNHH Cơ khí & Điện tử Bắc Giang',
            'tax_code' => '2400123456',
            'industry' => 'Sản xuất linh kiện điện tử',
            'employee_scale' => '500 - 1000 lao động',
            'phone' => '0204 3888 999',
            'website' => 'https://bacgiangelec.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Tạo / Lấy User Doanh nghiệp mẫu
        $companyUser = DB::table('users')->where('email', 'contact@bacgiangelec.com')->first();
        if (!$companyUser) {
            $userId = DB::table('users')->insertGetId([
                'name' => 'Đại diện Công ty Cơ khí & Điện tử',
                'email' => 'contact@bacgiangelec.com',
                'password' => Hash::make('12345678'),
                'role' => 'company',
                'company_id' => $companyId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $userId = $companyUser->id;
        }

        // 7. Tạo Tin tuyển dụng
        DB::table('recruitment_news')->insert([
            [
                'company_id' => $companyId,
                'title' => 'Kỹ sư Vận hành Máy SMT',
                'quantity' => 5,
                'salary_range' => '12 - 18 triệu',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $companyId,
                'title' => 'Công nhân Lắp ráp Linh kiện',
                'quantity' => 50,
                'salary_range' => '8 - 11 triệu',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        // 8. Tạo Hố ga Nước thải (Điểm Point màu tím sát mép xưởng)
        $hgtId = DB::table('wastewater_manholes')->insertGetId([
            'company_id' => $companyId,
            'factory_id' => $f1,
            'code' => 'HGT-SK-01',
            'depth' => 1.80,
            'pipe_diameter' => 'D200',
            'status' => 'active',
            'note' => 'Hố ga xả thải điểm đấu nối trục đường N1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::statement("
            UPDATE wastewater_manholes 
            SET geom = ST_GeomFromText('POINT(106.1770 21.2460)', 4326)
            WHERE id = {$hgtId}
        ");

        // 9. Tạo Hố ga Nước mưa (Điểm Point màu xanh dương)
        $hgmId = DB::table('rainwater_manholes')->insertGetId([
            'company_id' => $companyId,
            'factory_id' => $f1,
            'code' => 'HGM-SK-01',
            'depth' => 1.50,
            'cover_material' => 'Composite',
            'status' => 'active',
            'note' => 'Hố ga thu nước mưa mặt đường',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::statement("
            UPDATE rainwater_manholes 
            SET geom = ST_GeomFromText('POINT(106.1773 21.2460)', 4326)
            WHERE id = {$hgmId}
        ");

        // Tạo tài khoản Admin KCN
        DB::table('users')->updateOrInsert(
            ['email' => 'admin@gmail.vn'],
            [
                'name' => 'Khổng Văn Sứng',
                'password' => Hash::make('12345678'),
                'role' => 'admin',
                'is_blocked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}