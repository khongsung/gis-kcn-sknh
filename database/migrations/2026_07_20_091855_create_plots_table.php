<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreatePlotsTable extends Migration
{
    public function up()
    {
        Schema::create('plots', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Mã lô đất: CN-01, CN-02');
            $table->decimal('total_area', 12, 2)->nullable()->comment('Diện tích m2');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Thêm cột Spatial Geometry Polygon (Chuẩn tọa độ WGS84 - SRID 4326)
        DB::statement('ALTER TABLE plots ADD COLUMN geom geometry(Polygon, 4326);');
    }

    public function down()
    {
        Schema::dropIfExists('plots');
    }
}