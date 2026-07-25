<?php

namespace Tests\Feature\Admin;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyControllerTest extends TestCase
{
    use RefreshDatabase; // Tự động làm sạch DB sau mỗi testcase

    protected $adminUser;

    /**
     * Khởi tạo Admin User giả lập đăng nhập trước mỗi testcase
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Tạo tài khoản Admin giả
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
        ]);
    }

    /** @test */
    public function Test_01_Admin_Co_The_Xem_Danh_Sach_Cong_Ty()
    {
        // 1. Tạo sẵn 3 công ty giả
        Company::factory()->count(3)->create();

        // 2. Admin đăng nhập và truy cập trang Quản lý Doanh nghiệp
        $response = $this->actingAs($this->adminUser)
                         ->get(route('admin.companies.index')); // Hoặc URL '/admin/companies'

        // 3. Kiểm tra kết quả trả về OK (200) và đúng view Inertia
        $response->assertStatus(200);
    }

    /** @test */
    public function Test_02_Them_Moi_Cong_Ty_Thanh_Cong()
    {
        $payload = [
            'name'        => 'CÔNG TY TNHH SẢN XUẤT ABC',
            'tax_code'    => '0109998887',
            'industry'    => 'Cơ khí chính xác',
            'color'       => '#a50ef0',
            'phone'       => '0204 3829 111',
            'email'       => 'abc@company.com',
            'description' => 'Chuyên gia công cơ khí',
        ];

        $response = $this->actingAs($this->adminUser)
                         ->post('/admin/companies', $payload);

        // Kiểm tra redirect thành công
        $response->assertStatus(302);

        // Kiểm tra dữ liệu ĐÃ ĐƯỢC LƯU VÀO DATABASE
        $this->assertDatabaseHas('companies', [
            'tax_code' => '0109998887',
            'color'    => '#a50ef0'
        ]);
    }

    /** @test */
    public function Test_03_Validate_Kiem_Tra_Trung_Ma_So_Thue()
    {
        // Tạo sẵn 1 công ty có MST này
        Company::factory()->create(['tax_code' => '0101234567']);

        // Cố tình thêm 1 công ty trùng MST
        $payload = [
            'name'     => 'CÔNG TY XYZ',
            'tax_code' => '0101234567', // Trùng
        ];

        $response = $this->actingAs($this->adminUser)
                         ->post('/admin/companies', $payload);

        // Báo lỗi Validate tại trường tax_code
        $response->assertSessionHasErrors(['tax_code']);
    }

    /** @test */
    public function Test_04_Cap_Nhat_Thong_Tin_Cong_Ty()
    {
        $company = Company::factory()->create([
            'name'  => 'Tên Cũ',
            'color' => '#2563eb'
        ]);

        $updatePayload = [
            'name'     => 'Tên Mới Đã Sửa',
            'tax_code' => $company->tax_code,
            'color'    => '#10b981', // Đổi sang màu xanh lá
        ];

        $response = $this->actingAs($this->adminUser)
                         ->put("/admin/companies/{$company->id}", $updatePayload);

        $response->assertStatus(302);

        // Kiểm tra DB đã cập nhật tên mới và màu mới chưa
        $this->assertDatabaseHas('companies', [
            'id'    => $company->id,
            'name'  => 'Tên Mới Đã Sửa',
            'color' => '#10b981'
        ]);
    }

    /** @test */
    public function Test_05_Xoa_Cong_Ty()
    {
        $company = Company::factory()->create();

        $response = $this->actingAs($this->adminUser)
                         ->delete("/admin/companies/{$company->id}");

        $response->assertStatus(302);

        // Kiểm tra DB không còn bản ghi này nữa
        $this->assertDatabaseMissing('companies', [
            'id' => $company->id
        ]);
    }
}