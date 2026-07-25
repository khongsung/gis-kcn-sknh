<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\WaterBody;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaterBodyControllerTest extends TestCase
{
    use RefreshDatabase; // Làm sạch DB tự động sau mỗi testcase

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Giả lập tài khoản Admin đăng nhập
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
        ]);
    }

    /** @test */
    public function test_01_admin_co_the_xem_danh_sach_vung_mat_nuoc()
    {
        // Tạo sẵn 3 hồ điều hòa
        WaterBody::factory()->count(3)->create();

        $response = $this->actingAs($this->adminUser)
                         ->get(route('admin.water-bodies.index')); // Hoặc URL '/admin/water-bodies'

        $response->assertStatus(200);
    }

    /** @test */
    public function test_02_them_moi_vung_mat_nuoc_thanh_cong()
    {
        $sampleGeoJson = json_encode([
            'type' => 'Polygon',
            'coordinates' => [
                [[106.175, 21.245], [106.179, 21.245], [106.179, 21.248], [106.175, 21.248], [106.175, 21.245]]
            ]
        ]);

        $payload = [
            'code'        => 'MN-01',
            'area'        => 15000.50,
            'description' => 'Hồ điều hòa khu vực phía Bắc KCN',
            'geom'        => $sampleGeoJson,
        ];

        $response = $this->actingAs($this->adminUser)
                         ->post('/admin/water-bodies', $payload);

        $response->assertStatus(302);

        // Kiểm tra dữ liệu ĐÃ LƯU VÀO DATABASE
        $this->assertDatabaseHas('water_bodies', [
            'code' => 'MN-01',
            'area' => 15000.50,
        ]);
    }

    /** @test */
    public function test_03_validate_tu_choi_khi_nhap_trung_ma_vung_mat_nuoc()
    {
        WaterBody::factory()->create(['code' => 'MN-99']);

        $payload = [
            'code' => 'MN-99', // Trùng mã
            'area' => 5000,
        ];

        $response = $this->actingAs($this->adminUser)
                         ->post('/admin/water-bodies', $payload);

        // Bắt buộc báo lỗi validate ở trường 'code'
        $response->assertSessionHasErrors(['code']);
    }

    /** @test */
    public function test_04_cap_nhat_thong_tin_vung_mat_nuoc()
    {
        $waterBody = WaterBody::factory()->create([
            'code' => 'MN-02',
            'area' => 8000,
        ]);

        $updatePayload = [
            'code'        => 'MN-02',
            'area'        => 9500.00, // Mở rộng lòng hồ
            'description' => 'Đã nạo vét và tăng dung tích chứa nước',
        ];

        $response = $this->actingAs($this->adminUser)
                         ->put("/admin/water-bodies/{$waterBody->id}", $updatePayload);

        $response->assertStatus(302);

        // Kiểm tra DB đã ghi nhận thông số mới
        $this->assertDatabaseHas('water_bodies', [
            'id'   => $waterBody->id,
            'area' => 9500.00,
        ]);
    }

    /** @test */
    public function test_05_xoa_vung_mat_nuoc_thanh_cong()
    {
        $waterBody = WaterBody::factory()->create();

        $response = $this->actingAs($this->adminUser)
                         ->delete("/admin/water-bodies/{$waterBody->id}");

        $response->assertStatus(302);

        // Kiểm tra đã xóa khỏi DB
        $this->assertDatabaseMissing('water_bodies', [
            'id' => $waterBody->id,
        ]);
    }
}