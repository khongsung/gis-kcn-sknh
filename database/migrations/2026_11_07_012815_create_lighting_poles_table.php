<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLightingPolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lighting_poles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('industrial_zones')->cascadeOnDelete();
            $table->foreignId('cabinet_id')->nullable()->constrained('lighting_cabinets')->nullOnDelete();
            $table->foreignId('road_id')->nullable()->constrained('roads')->nullOnDelete();

            $table->string('code', 50)->unique()->comment('Mã cột, VD: C-CS-D1-01, C-02');
            $table->decimal('height_m', 5, 2)->default(8.0)->comment('Chiều cao cột (mét), VD: 8m, 10m, 12m');
            $table->string('pole_material', 100)->default('Thép mạ kẽm')->comment('Chất liệu: Thép mạ kẽm, Bê tông ly tâm');
            $table->string('arm_type', 100)->nullable()->comment('Cần đèn: Cần đơn, Cần đôi, Cần uốn mỹ thuật');
            $table->string('lamp_type', 100)->default('LED')->comment('Loại bóng: LED, Cao áp Sodium, Metal Halide');
            $table->decimal('wattage', 6, 2)->default(150)->comment('Công suất bóng (W), VD: 120W, 150W, 250W');
            $table->enum('status', ['working', 'broken', 'maintenance'])->default('working')->comment('Trạng thái hoạt động');
            $table->date('installation_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Tọa độ vị trí đặt cột (Point, 4326)
        DB::statement('ALTER TABLE lighting_poles ADD COLUMN geom geometry(Point, 4326)');
        DB::statement('CREATE INDEX idx_lighting_poles_geom ON lighting_poles USING GIST (geom)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lighting_poles');
    }
}
