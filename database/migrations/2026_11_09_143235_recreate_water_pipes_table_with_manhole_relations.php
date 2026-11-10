<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Xóa bảng cũ
        Schema::dropIfExists('water_pipes');

        // 2. Tạo bảng water_pipes mới
        Schema::create('water_pipes', function (Blueprint $table) {
            $table->id();

            // Phân biệt Khu công nghiệp
            $table->foreignId('zone_id')
                  ->nullable()
                  ->constrained('industrial_zones')
                  ->nullOnDelete();

            // Tuyến đường giao thông liên kết
            $table->foreignId('road_id')
                  ->nullable()
                  ->constrained('roads')
                  ->nullOnDelete();

            // Phân loại hệ thống (Quyết định trỏ tới bảng hố ga nào)
            $table->enum('pipe_type', ['wastewater', 'rainwater'])
                  ->default('wastewater')
                  ->comment('wastewater: Nước thải, rainwater: Nước mưa');

            // Định danh tuyến cống
            $table->string('code', 50)->unique()->comment('Mã đoạn cống: NT-D1-01, NM-TRUC-02...');
            $table->string('name')->nullable()->comment('Tên tuyến/đoạn cống');

            // 🌟 Khóa ngoại trỏ đến Hố ga đầu và cuối chính của tuyến
            // (ID sẽ tham chiếu tới wastewater_manholes hoặc rainwater_manholes tùy vào pipe_type)
            $table->unsignedBigInteger('start_manhole_id')->nullable()->comment('ID hố ga thượng lưu');
            $table->unsignedBigInteger('end_manhole_id')->nullable()->comment('ID hố ga hạ lưu');

            // Quy cách & Vật liệu
            $table->enum('cross_section', ['circular', 'box', 'open_channel'])->default('circular');
            $table->integer('diameter')->nullable()->comment('Đường kính tròn (mm)');
            $table->integer('width')->nullable()->comment('Rộng cống hộp/mương (mm)');
            $table->integer('height')->nullable()->comment('Cao cống hộp/mương (mm)');
            $table->string('material', 50)->nullable()->comment('HDPE 2 vách, BTCT, uPVC...');

            // Cốt đáy cống đầu, cống cuối và độ dốc tính toán
            $table->decimal('invert_start_elevation', 6, 3)->nullable()->comment('Cốt đáy cống đầu (m)');
            $table->decimal('invert_end_elevation', 6, 3)->nullable()->comment('Cốt đáy cống cuối (m)');
            $table->decimal('slope', 5, 3)->nullable()->comment('Độ dốc i (%)');

            // Trạng thái vận hành
            $table->enum('status', ['active', 'clogged', 'damaged', 'maintenance'])->default('active');
            $table->date('last_inspected_at')->nullable();
            $table->text('description')->nullable();

            $table->timestamps();

            // Index tối ưu truy vấn
            $table->index('zone_id');
            $table->index('road_id');
            $table->index('pipe_type');
            $table->index(['start_manhole_id', 'end_manhole_id']);
        });

        // 3. Cột không gian PostGIS MultiLineString SRID 4326
        DB::statement('ALTER TABLE water_pipes ADD COLUMN geom geometry(MultiLineString, 4326);');
        DB::statement('CREATE INDEX idx_water_pipes_geom ON water_pipes USING GIST(geom);');
    }

    public function down(): void
    {
        Schema::dropIfExists('water_pipes');
    }
};