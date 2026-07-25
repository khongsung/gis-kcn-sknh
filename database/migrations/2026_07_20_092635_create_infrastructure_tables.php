<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateInfrastructureTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Bảng Đường giao thông
        Schema::create('roads', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Tên đường');
            $table->decimal('right_of_way', 8, 2)->nullable()->comment('Lộ giới (m)');
            $table->decimal('road_width', 8, 2)->nullable()->comment('Lòng đường (m)');
            $table->timestamps();
        });
        DB::statement('ALTER TABLE roads ADD COLUMN geom geometry(LineString, 4326);');

        // 2. Bảng Đường ống Cấp / Thoát nước
        Schema::create('water_pipes', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['supply', 'drainage', 'wastewater'])->default('supply');
            $table->integer('diameter')->nullable()->comment('Đường kính ống mm');
            $table->string('material')->nullable();
            $table->timestamps();
        });
        DB::statement('ALTER TABLE water_pipes ADD COLUMN geom geometry(LineString, 4326);');

        // 3. Bảng Trạm điện / Hạ tầng điểm
        Schema::create('substations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Tên trạm điện');
            $table->string('capacity')->nullable()->comment('Công suất kVA');
            $table->timestamps();
        });
        DB::statement('ALTER TABLE substations ADD COLUMN geom geometry(Point, 4326);');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('substations');
        Schema::dropIfExists('water_pipes');
        Schema::dropIfExists('roads');
    }
}
