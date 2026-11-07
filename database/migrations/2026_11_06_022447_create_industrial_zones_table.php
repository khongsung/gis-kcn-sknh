<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIndustrialZonesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Kích hoạt PostGIS nếu chưa có
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');

        Schema::create('industrial_zones', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Mã viết tắt KCN: SK_NH, DT');
            $table->string('name', 255)->comment('Tên đầy đủ KCN');
            $table->string('province', 100)->default('Thành phố Bắc Ninh')->comment('Tỉnh/Thành phố');
            $table->string('address', 255)->nullable()->comment('Địa chỉ / Vị trí');
            
            // Tọa độ tâm bản đồ và mức zoom mặc định phục vụ flyTo trên WebGIS
            $table->decimal('center_lat', 10, 7)->comment('Vĩ độ tâm KCN');
            $table->decimal('center_lng', 10, 7)->comment('Kinh độ tâm KCN');
            $table->unsignedTinyInteger('default_zoom')->default(16)->comment('Mức zoom phù hợp khi chuyển vùng');

            // Tổng diện tích quy hoạch đất theo quyết định (m2)
            $table->decimal('total_area', 14, 2)->nullable()->comment('Tổng diện tích KCN (m2)');
            $table->text('description')->nullable()->comment('Mô tả, ghi chú tổng quan về KCN');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Cột hình học Polygon ranh giới bao quát toàn khu (WGS84 - SRID 4326)
        DB::statement('ALTER TABLE industrial_zones ADD COLUMN boundary geometry(Polygon, 4326)');
        DB::statement('CREATE INDEX idx_industrial_zones_boundary ON industrial_zones USING GIST (boundary)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('industrial_zones');
    }
}
