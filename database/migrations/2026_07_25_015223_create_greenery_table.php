<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGreeneryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('greenery', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Mã vùng cây xanh / thảm cỏ: CX-01, CX-02...');
            $table->decimal('area', 10, 2)->nullable()->comment('Diện tích cây xanh (m2)');
            $table->text('description')->nullable()->comment('Ghi chú, loại cây xanh, chủng loại thảm cỏ');
            $table->timestamps();
        });

        // Thêm cột không gian geom dạng Polygon (PostGIS, WGS84 SRID 4326)
        DB::statement('ALTER TABLE greenery ADD COLUMN geom geometry(Polygon, 4326);');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('greenery');
    }
}
