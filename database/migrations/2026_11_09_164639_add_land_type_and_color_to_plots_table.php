<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLandTypeAndColorToPlotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('plots', function (Blueprint $table) {
            // Loại đất: industrial (Đất công nghiệp), commercial (Đất thương mại dịch vụ)
            $table->string('land_type', 50)
                  ->default('industrial')
                  ->after('total_area')
                  ->comment('industrial: Đất công nghiệp, commercial: Đất TMDV');

            // Cột mã màu Hex: Mặc định #b45309 (Đất CN), Đất TMDV dùng #e11d48 (Hồng đỏ)
            $table->string('color', 20)
                  ->default('#b45309')
                  ->after('land_type')
                  ->comment('Mã màu hex hiển thị lên bản đồ WebGIS');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('plots', function (Blueprint $table) {
            $table->dropColumn(['land_type', 'color']);
        });
    }
}
