<?php

namespace Tests\Feature\Admin;

use App\Models\Greenery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GreeneryControllerTest extends TestCase
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
    public function test_01_admin_co_the_xem_danh_sach_khu_vuc_cay_xanh()
    {
        // Tạo sẵn 3 vùng cây xanh
        Greenery::factory()->count(3)->create();

        $response = $this->actingAs($this->adminUser)
                         ->get(route('admin.greenery.index')); // Hoặc URL '/admin/greenery'

        $response->assertStatus(200);
    }

    /** @test */
    public function test_02_them_moi_vung_cay_xanh_kem_toa_do_geojson_thanh_cong()
    {
        $sampleGeoJson = json_encode([
            'type' => 'Polygon',
            'coordinates' => [
                [[106.171, 21.241], [106.174, 21.241], [106.174, 21.243], [106.171, 21.243], [106.171, 21.241]]
            ]
        ]);

        $payload = [
            'code'        => 'CX-101',
            'area'        => 2500.75,
            'description' => 'Thảm cỏ dải phân cách trung tâm',
            'geom'        => $sampleGeoJson,
        ];

        $response = $this->actingAs($this->adminUser)
                         ->post('/admin/greenery', $payload);

        $response->assertStatus(302);

        // Kiểm tra dữ liệu ĐÃ LƯU VÀO CƠ SỞ DỮ LIỆU
        $this->assertDatabaseHas('greenery', [
            'code' => 'CX-101',
            'area' => 2500.75,
        ]);
    }

    /** @test */
    public function test_03_validate_tu_choi_khi_nhap_trung_ma_vung_cay_xanh()
    {
        Greenery::factory()->create(['code' => 'CX-001']);

        $payload = [
            'code' => 'CX-001', // Cố tình trùng mã
            'area' => 1000,
        ];

        $response = $this->actingAs($this->adminUser)
                         ->post('/admin/greenery', $payload);

        // Hệ thống từ chối và báo lỗi Validate ở trường 'code'
        $response->assertSessionHasErrors(['code']);
    }

    /** @test */
    public function test_04_cap_nhat_thong_tin_va_dien_tich_vung_cay_xanh()
    {
        $greenery = Greenery::factory()->create([
            'code' => 'CX-002',
            'area' => 1500,
        ]);

        $updatePayload = [
            'code'        => 'CX-002',
            'area'        => 1800.50, // Cập nhật diện tích mở rộng
            'description' => 'Bổ sung thêm 50 cây xanh thân gỗ',
        ];

        $response = $this->actingAs($this->adminUser)
                         ->put("/admin/greenery/{$greenery->id}", $updatePayload);

        $response->assertStatus(302);

        // Kiểm tra DB đã cập nhật diện tích mới
        $this->assertDatabaseHas('greenery', [
            'id'   => $greenery->id,
            'area' => 1800.50,
        ]);
    }

    /** @test */
    public function test_05_xoa_vung_cay_xanh_thanh_cong()
    {
        $greenery = Greenery::factory()->create();

        $response = $this->actingAs($this->adminUser)
                         ->delete("/admin/greenery/{$greenery->id}");

        $response->assertStatus(302);

        // Kiểm tra vùng cây xanh đã bị xóa khỏi cơ sở dữ liệu
        $this->assertDatabaseMissing('greenery', [
            'id' => $greenery->id,
        ]);
    }
}