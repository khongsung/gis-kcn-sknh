<?php

namespace Tests\Feature\Admin;

use App\Models\Company;
use App\Models\Plot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlotControllerTest extends TestCase
{
    use RefreshDatabase; // Tự động làm sạch Database sau mỗi testcase

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Tạo sẵn tài khoản Admin giả lập đăng nhập
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
        ]);
    }

    /** @test */
    public function test_01_admin_co_the_xem_danh_sach_lo_dat()
    {
        // Tạo sẵn 2 lô đất
        Plot::factory()->count(2)->create();

        $response = $this->actingAs($this->adminUser)
                         ->get(route('admin.plots.index')); // Hoặc URL '/admin/plots'

        $response->assertStatus(200);
    }

    /** @test */
    public function test_02_them_moi_lo_dat_va_lien_ket_cong_ty_thue_thanh_cong()
    {
        // 1. Tạo 2 công ty thuê trước
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        // Chuỗi GeoJSON đại diện cho Polygon tọa độ Lô đất
        $sampleGeoJson = json_encode([
            'type' => 'Polygon',
            'coordinates' => [
                [[106.173, 21.241], [106.175, 21.241], [106.175, 21.243], [106.173, 21.243], [106.173, 21.241]]
            ]
        ]);

        $payload = [
            'code'        => 'CN-08',
            'area'        => 12500.50,
            'description' => 'Lô đất góc 2 mặt tiền đường chính KCN',
            'status'      => 'rented',
            'geojson'     => $sampleGeoJson,
            'company_ids' => [$company1->id, $company2->id], // Gán cả 2 công ty thuê
        ];

        $response = $this->actingAs($this->adminUser)
                         ->post('/admin/plots', $payload);

        $response->assertStatus(302);

        // 2. Kiểm tra dữ liệu ĐÃ ĐƯỢC LƯU VÀO BẢNG 'plots'
        $this->assertDatabaseHas('plots', [
            'code'       => 'CN-08',
            'total_area' => 12500.50,
        ]);

        // 3. Kiểm tra BẢNG TRUNG GIANG 'company_plot' đã gán đúng liên kết N-N chưa
        $plot = Plot::where('code', 'CN-08')->first();
        $this->assertDatabaseHas('company_plot', [
            'plot_id'    => $plot->id,
            'company_id' => $company1->id,
        ]);
        $this->assertDatabaseHas('company_plot', [
            'plot_id'    => $plot->id,
            'company_id' => $company2->id,
        ]);
    }

    /** @test */
    public function test_03_validate_loi_khi_nhap_trung_ma_lo_dat()
    {
        Plot::factory()->create(['code' => 'CN-99']);

        $payload = [
            'code' => 'CN-99', // Cố tình nhập trùng
            'area' => 5000,
        ];

        $response = $this->actingAs($this->adminUser)
                         ->post('/admin/plots', $payload);

        // Bắt buộc hệ thống từ chối và báo lỗi Validate ở trường 'code'
        $response->assertSessionHasErrors(['code']);
    }

    /** @test */
    public function test_04_cap_nhat_lo_dat_va_dong_bo_lai_danh_sach_cong_ty()
    {
        $companyOld = Company::factory()->create();
        $companyNew = Company::factory()->create();

        // 1. Tạo lô đất ban đầu gắn với companyOld
        $plot = Plot::factory()->create([
            'code'       => 'CN-01',
            'total_area' => 10000,
        ]);
        $plot->companies()->attach($companyOld->id);

        // 2. Tiến hành Cập nhật: Đổi diện tích và chuyển quyền thuê cho companyNew
        $updatePayload = [
            'code'        => 'CN-01',
            'area'        => 15000, // Đổi diện tích mới
            'status'      => 'rented',
            'description' => 'Đã mở rộng diện tích lô đất',
            'company_ids' => [$companyNew->id], // Chuyển sang companyNew
        ];

        $response = $this->actingAs($this->adminUser)
                         ->put("/admin/plots/{$plot->id}", $updatePayload);

        $response->assertStatus(302);

        // 3. Kiểm tra thông tin lô đất trong DB đã cập nhật diện tích mới
        $this->assertDatabaseHas('plots', [
            'id'         => $plot->id,
            'total_area' => 15000,
        ]);

        // 4. Kiểm tra liên kết cũ bị hủy, liên kết mới được thay thế thành công qua sync()
        $this->assertDatabaseMissing('company_plot', [
            'plot_id'    => $plot->id,
            'company_id' => $companyOld->id,
        ]);
        $this->assertDatabaseHas('company_plot', [
            'plot_id'    => $plot->id,
            'company_id' => $companyNew->id,
        ]);
    }

    /** @test */
    public function test_05_xoa_lo_dat_thanh_cong()
    {
        $company = Company::factory()->create();
        $plot = Plot::factory()->create();
        $plot->companies()->attach($company->id);

        $response = $this->actingAs($this->adminUser)
                         ->delete("/admin/plots/{$plot->id}");

        $response->assertStatus(302);

        // Kiểm tra lô đất đã bị xóa khỏi bảng 'plots'
        $this->assertDatabaseMissing('plots', [
            'id' => $plot->id,
        ]);

        // Kiểm tra liên kết trong bảng trung gian 'company_plot' cũng được hủy sạch
        $this->assertDatabaseMissing('company_plot', [
            'plot_id' => $plot->id,
        ]);
    }
}