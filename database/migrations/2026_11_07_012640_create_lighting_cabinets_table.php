<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLightingCabinetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('lighting_cabinets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('industrial_zones')->cascadeOnDelete();
            $table->foreignId('road_id')->nullable()->constrained('roads')->nullOnDelete();

            $table->string('code', 50)->unique()->comment('Mã tủ, VD: TBA-CS-01, TDK-01');
            $table->string('name', 255)->comment('Tên tủ điện điều khiển');
            $table->decimal('capacity_kva', 8, 2)->nullable()->comment('Công suất thiết kế (kVA/kW)');
            $table->string('control_type', 100)->default('timer')->comment('Loại điều khiển: timer (hẹn giờ), photocell (cảm biến ánh sáng), smart_iot');
            $table->string('power_source', 255)->nullable()->comment('Nguồn cấp từ Trạm biến áp nào');
            $table->unsignedInteger('channels_count')->default(2)->comment('Số lộ/nhánh ra');
            $table->enum('status', ['active', 'maintenance', 'inactive'])->default('active')->comment('Trạng thái hoạt động');
            $table->date('installation_date')->nullable()->comment('Ngày đóng điện/lắp đặt');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Tọa độ vị trí tủ điện (Point, 4326)
        DB::statement('ALTER TABLE lighting_cabinets ADD COLUMN geom geometry(Point, 4326)');
        DB::statement('CREATE INDEX idx_lighting_cabinets_geom ON lighting_cabinets USING GIST (geom)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lighting_cabinets');
    }
}
