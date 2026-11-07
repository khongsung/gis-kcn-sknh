<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSurroundingLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('surrounding_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')
                  ->nullable()
                  ->constrained('industrial_zones')
                  ->nullOnDelete();
            
            $table->string('code', 50)->unique();             // Mã vùng (VD: KDC-SK, LOG-NH)
            $table->string('name', 255);                     // Tên hiển thị trên nhãn bản đồ
            $table->string('category', 100)->nullable();     // Phân loại: residential, logistics, transport, industrial
            $table->string('bg_color', 20)->default('#1e293b'); // Màu nền badge nhãn
            $table->string('text_color', 20)->default('#ffffff'); // Màu chữ
            $table->string('icon', 10)->nullable();          // Icon emoji hiển thị kèm (🏘️, 🚚, 🛣️)
            $table->text('description')->nullable();         // Mô tả tóm tắt
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Bổ sung cột geom Point WGS84 (SRID 4326) qua PostGIS
        DB::statement("SELECT AddGeometryColumn('surrounding_locations', 'geom', 4326, 'POINT', 2)");
        
        // Tạo chỉ mục không gian GIST cho PostGIS
        DB::statement("CREATE INDEX idx_surrounding_locations_geom ON surrounding_locations USING GIST (geom)");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('surrounding_locations');
    }
}
