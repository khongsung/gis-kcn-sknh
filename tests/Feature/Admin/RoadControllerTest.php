<?php

namespace Tests\Feature\Admin;

use App\Models\Road;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoadControllerTest extends TestCase
{
    use RefreshDatabase; // Tự động làm sạch Database Test sau mỗi testcase

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Tạo tài khoản Admin giả lập đăng nhập
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
        ]);
    }

    /** @test */
    public function test_01_admin_co_the_xem_danh_sach_tuyen_duong()
    {
        // 1. Tạo sẵn 3 tuyến đường mẫu
        Road::factory()->count(3)->create();

        // 2. Admin truy cập trang danh sách đường
        $response = $this->actingAs($this->adminUser)
                         ->get('/admin/roads');

        // 3. Kiểm tra HTTP Status 200 OK
        $response->assertStatus(200);
    }

    /** @test */
    public function test_02_them_moi_tuyen_duong_kem_toa_do_linestring_thanh_cong()
    {
        $sampleGeoJson = json_encode([
            'type' => 'LineString',
            'coordinates' => [
                [106.171, 21.241],
                [106.175, 21.243],
                [106.180, 21.246]
            ]
        ]);

        $payload = [
            'name'         => 'Đường Trục Chính D1',
            'right_of_way' => 31.00,
            'road_width'   => 15.00,
            'geojson'      => $sampleGeoJson,
        ];

        $response = $this->actingAs($this->adminUser)
                         ->post('/admin/roads', $payload);

        // Kiểm tra chuyển hướng thành công
        $response->assertStatus(302);

        // Kiểm tra dữ liệu ĐÃ LƯU VÀO DATABASE
        $this->assertDatabaseHas('roads', [
            'name'         => 'Đường Trục Chính D1',
            'right_of_way' => 31.00,
            'road_width'   => 15.00,
        ]);
    }

    /** @test */
    public function test_03_validate_tu_choi_khi_de_trong_ten_tuyen_duong()
    {
        $payload = [
            'name'         => '', // Cố tình để trống tên đường
            'right_of_way' => 25.00,
        ];

        $response = $this->actingAs($this->adminUser)
                         ->post('/admin/roads', $payload);

        // Hệ thống báo lỗi Validate ở trường 'name'
        $response->assertSessionHasErrors(['name']);
    }

    /** @test */
    public function test_04_cap_nhat_thong_tin_va_mat_cat_tuyen_duong()
    {
        $road = Road::factory()->create([
            'name'       => 'Đường N2',
            'road_width' => 10.50,
        ]);

        $updatePayload = [
            'name'         => 'Đường N2 (Mở rộng)',
            'right_of_way' => 25.00,
            'road_width'   => 15.00, // Mở rộng lòng đường
        ];

        $response = $this->actingAs($this->adminUser)
                         ->put("/admin/roads/{$road->id}", $updatePayload);

        $response->assertStatus(302);

        // Kiểm tra DB đã cập nhật thông số mới
        $this->assertDatabaseHas('roads', [
            'id'         => $road->id,
            'name'       => 'Đường N2 (Mở rộng)',
            'road_width' => 15.00,
        ]);
    }

    /** @test */
    public function test_05_xoa_tuyen_duong_thanh_cong()
    {
        $road = Road::factory()->create();

        $response = $this->actingAs($this->adminUser)
                         ->delete("/admin/roads/{$road->id}");

        $response->assertStatus(302);

        // Kiểm tra đã xóa hoàn toàn khỏi DB
        $this->assertDatabaseMissing('roads', [
            'id' => $road->id,
        ]);
    }
}