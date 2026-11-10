<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_lands', function (Blueprint $table) {
            $table->id();

            // Khóa ngoại liên kết
            $table->foreignId('zone_id')->nullable()->constrained('industrial_zones')->nullOnDelete();
            $table->foreignId('plot_id')->nullable()->constrained('plots')->nullOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();

            // Mã định danh thửa đất
            $table->string('land_code', 50)->unique()->comment('Mã mảnh đất: DN-LO01-A');

            // Pháp lý Giấy chứng nhận QSDĐ (Sổ bìa đỏ)
            $table->string('certificate_number')->nullable()->comment('Số GCN QSDĐ / Số seri bìa đỏ');
            $table->string('cadastral_book_number')->nullable()->comment('Số vào sổ cấp GCN');
            $table->date('issue_date')->nullable()->comment('Ngày cấp sổ');
            $table->string('issuing_authority')->nullable()->default('UBND tỉnh Bắc Giang');

            // Hợp đồng thuê lại đất
            $table->string('contract_number')->nullable()->comment('Số HĐ thuê đất');
            $table->date('contract_date')->nullable();
            $table->date('lease_start_date')->nullable()->comment('Ngày bắt đầu');
            $table->date('lease_end_date')->nullable()->comment('Ngày kết thúc thời hạn thuê');
            $table->enum('payment_type', ['one_time', 'annual'])->default('one_time')->comment('Trả 1 lần hoặc hàng năm');

            // Diện tích (m2)
            $table->decimal('leased_area', 12, 2)->nullable()->comment('Diện tích pháp lý trên sổ (m2)');
            $table->decimal('calculated_area', 12, 2)->nullable()->comment('Diện tích trắc địa tự động tính từ GIS (m2)');

            // Chỉ tiêu quy hoạch kiến trúc
            $table->string('land_purpose')->nullable()->comment('Mục đích sử dụng đất');
            $table->decimal('building_density_limit', 5, 2)->nullable()->comment('Mật độ XD tối đa (%)');

            // Hiện trạng vận hành
            $table->enum('status', ['active', 'under_construction', 'vacant', 'disputed'])->default('active');
            $table->boolean('is_handed_over')->default(true)->comment('Đã bàn giao mốc cắm thực địa');
            $table->text('description')->nullable();

            $table->timestamps();

            // Index
            $table->index('zone_id');
            $table->index('plot_id');
            $table->index('company_id');
            $table->index('lease_end_date');
        });

        // Thêm cột Polygon PostGIS SRID 4326 và GIST Index
        DB::statement('ALTER TABLE company_lands ADD COLUMN geom geometry(Polygon, 4326);');
        DB::statement('CREATE INDEX idx_company_lands_geom ON company_lands USING GIST(geom);');
    }

    public function down(): void
    {
        Schema::dropIfExists('company_lands');
    }
};