<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIndustrialZoneBoundariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');

        Schema::create('industrial_zone_boundaries', function (Blueprint $table) {
            $table->id();
            
            // Khóa ngoại liên kết tới bảng khu công nghiệp (nếu đã có bảng industrial_zones)
            // Nếu chưa có bảng industrial_zones, bạn có thể tạm để: $table->unsignedBigInteger('zone_id')->nullable();
            $table->foreignId('zone_id')
                  ->nullable()
                  ->constrained('industrial_zones')
                  ->nullOnDelete();

            $table->string('code', 50)->unique()->comment('Mã ranh giới quy hoạch, ví dụ: RG-SKNH, RG-DT');
            $table->string('name', 255)->comment('Tên ranh giới / Phân khu quy hoạch');
            $table->string('decision_number', 100)->nullable()->comment('Số quyết định phê duyệt (VD: 123/QĐ-UBND)');
            $table->date('approval_date')->nullable()->comment('Ngày ban hành quyết định phê duyệt');
            $table->decimal('total_planning_area', 14, 2)->nullable()->comment('Diện tích quy hoạch (m2)');
            $table->text('legal_basis')->nullable()->comment('Căn cứ pháp lý, quy hoạch chi tiết 1/2000 hoặc 1/500');
            $table->string('fill_color', 20)->default('#e11d48')->comment('Mã màu đường viền hiển thị trên bản đồ');
            $table->timestamps();
        });

        // 3. Thêm cột hình học GEOMETRY (MultiPolygon, WGS84 - SRID 4326) và Index không gian GIST
        // Dùng MultiPolygon để xử lý được cả trường hợp KCN gồm nhiều phân khu tách rời nhau
        DB::statement('ALTER TABLE industrial_zone_boundaries ADD COLUMN geom geometry(LineString, 4326)');
        DB::statement('CREATE INDEX idx_iz_boundaries_geom ON industrial_zone_boundaries USING GIST (geom)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('industrial_zone_boundaries');
    }
}
