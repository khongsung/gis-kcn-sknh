<?php

namespace Tests\Feature\Admin;

use App\Models\Company;
use App\Models\Factory;
use App\Models\Plot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactoryControllerTest extends TestCase
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
    public function test_01_admin_co_the_xem_danh_sach_nha_xuong()
    {
        // Tạo sẵn 3 nhà xưởng
        Factory::factory()->count(3)->create();

        $response = $this->actingAs($this->adminUser)
                         ->get(route('admin.factories.index')); // Hoặc URL '/admin/factories'

        $response->assertStatus(200);
    }

    /** @test */
    public function test_02_them_moi_nha_xuong_kem_toa_do_geojson_thanh_cong()
    {
        $plot = Plot::factory()->create();
        $company = Company::factory()->create();

        $sampleGeoJson = json_encode([
            'type' => 'Polygon',
            'coordinates' => [
                [[106.172, 21.242], [106.175, 21.242], [106.175, 21.245], [106.172, 21.245], [106.172, 21.242]]
            ]
        ]);

        $payload = [
            'code'       => 'NX-101',
            'area'       => 3500.50,
            'status'     => 'rented',
            'plot_id'    => $plot->id,
            'company_id' => $company->id,
            'geojson'    => $sampleGeoJson,
        ];

        $response = $this->actingAs($this->adminUser)
                         ->post('/admin/factories', $payload);

        $response->assertStatus(302);

        // Kiểm tra dữ liệu ĐÃ LƯU VÀO CƠ SỞ DỮ LIỆU
        $this->assertDatabaseHas('factories', [
            'code'       => 'NX-101',
            'status'     => 'rented',
            'company_id' => $company->id,
        ]);
    }

    /** @test */
    public function test_03_tu_dong_set_company_id_ve_null_neu_xuong_chua_cho_thue()
    {
        $plot = Plot::factory()->create();
        $company = Company::factory()->create();

        // Chọn trạng thái 'available' (Đang trống), gửi kèm company_id
        $payload = [
            'code'       => 'NX-102',
            'area'       => 2000,
            'status'     => 'available', // Chưa cho thuê
            'plot_id'    => $plot->id,
            'company_id' => $company->id, // Phải bị hủy do status không phải rented
        ];

        $response = $this->actingAs($this->adminUser)
                         ->post('/admin/factories', $payload);

        $response->assertStatus(302);

        // Kiểm tra company_id được gán về null tự động trong Controller
        $this->assertDatabaseHas('factories', [
            'code'       => 'NX-102',
            'company_id' => null,
        ]);
    }

    /** @test */
    public function test_04_validate_tu_choi_khi_nhap_trung_ma_nha_xuong()
    {
        Factory::factory()->create(['code' => 'NX-999']);

        $payload = [
            'code'   => 'NX-999', // Cố tình trùng mã
            'status' => 'available',
        ];

        $response = $this->actingAs($this->adminUser)
                         ->post('/admin/factories', $payload);

        // Hệ thống báo lỗi Validate ở trường 'code'
        $response->assertSessionHasErrors(['code']);
    }

    /** @test */
    public function test_05_cap_nhat_thong_tin_nha_xuong()
    {
        $factory = Factory::factory()->create([
            'code'   => 'NX-200',
            'status' => 'available',
        ]);

        $company = Company::factory()->create();

        $updatePayload = [
            'code'       => 'NX-200',
            'area'       => 4200.00,
            'status'     => 'rented', // Cập nhật sang Đã cho thuê
            'plot_id'    => $factory->plot_id,
            'company_id' => $company->id,
        ];

        $response = $this->actingAs($this->adminUser)
                         ->put("/admin/factories/{$factory->id}", $updatePayload);

        $response->assertStatus(302);

        // Kiểm tra DB đã ghi nhận trạng thái và công ty mới
        $this->assertDatabaseHas('factories', [
            'id'         => $factory->id,
            'status'     => 'rented',
            'company_id' => $company->id,
            'area'       => 4200.00,
        ]);
    }

    /** @test */
    public function test_06_xoa_nha_xuong_thanh_cong()
    {
        $factory = Factory::factory()->create();

        $response = $this->actingAs($this->adminUser)
                         ->delete("/admin/factories/{$factory->id}");

        $response->assertStatus(302);

        // Kiểm tra nhà xưởng đã bị xóa khỏi cơ sở dữ liệu
        $this->assertDatabaseMissing('factories', [
            'id' => $factory->id,
        ]);
    }
}