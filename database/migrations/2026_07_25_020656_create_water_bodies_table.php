<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWaterBodiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('water_bodies', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Mã diện tích mặt nước / hồ điều hòa: MN-01, MN-02...');
            $table->decimal('area', 10, 2)->nullable()->comment('Diện tích mặt nước (m2)');
            $table->text('description')->nullable()->comment('Ghi chú, loại hồ điều hòa, kênh mương, độ sâu trung bình');
            $table->timestamps();
        });

        // Thêm cột không gian geom dạng Polygon (PostGIS, WGS84 SRID 4326)
        DB::statement('ALTER TABLE water_bodies ADD COLUMN geom geometry(Polygon, 4326);');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('water_bodies');
    }
}
