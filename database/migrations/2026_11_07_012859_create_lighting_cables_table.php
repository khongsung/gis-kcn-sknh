<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLightingCablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lighting_cables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('industrial_zones')->cascadeOnDelete();
            $table->foreignId('cabinet_id')->nullable()->constrained('lighting_cabinets')->nullOnDelete();
            $table->foreignId('road_id')->nullable()->constrained('roads')->nullOnDelete();

            $table->string('code', 50)->unique()->comment('Mã lộ cáp, VD: CAP-CS-L01, L1-D1');
            $table->string('name', 255)->comment('Tên tuyến cáp chiếu sáng');
            $table->string('cable_spec', 150)->default('Cu/XLPE/PVC/DSTA/PVC 4x16mm2')->comment('Quy cách cáp ngầm');
            $table->enum('installation_type', ['underground', 'overhead'])->default('underground')->comment('Phương thức: Ngầm (underground) / Treo (overhead)');
            $table->decimal('depth_m', 4, 2)->nullable()->default(0.7)->comment('Độ sâu chôn cáp (m)');
            $table->string('conduit_type', 100)->nullable()->default('Ống nhựa xoắn HDPE D65/50')->comment('Loại ống luồn bảo vệ');
            $table->decimal('length_m', 10, 2)->nullable()->comment('Chiều dài tuyến cáp đo theo GIS (m)');
            $table->enum('status', ['normal', 'fault', 'repairing'])->default('normal')->comment('Tình trạng tuyến cáp');
            $table->timestamps();
        });

        // Tuyến đường đi của cáp (LineString, 4326)
        DB::statement('ALTER TABLE lighting_cables ADD COLUMN geom geometry(LineString, 4326)');
        DB::statement('CREATE INDEX idx_lighting_cables_geom ON lighting_cables USING GIST (geom)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lighting_cables');
    }
}
