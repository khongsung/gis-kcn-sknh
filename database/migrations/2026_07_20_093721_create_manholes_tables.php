<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateManholesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. BẢNG HỐ GA NƯỚC THẢI (Wastewater Manholes)
        Schema::create('wastewater_manholes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade')->comment('Thuộc Doanh nghiệp nào');
            $table->foreignId('factory_id')->nullable()->constrained('factories')->onDelete('cascade')->comment('Thuộc Nhà xưởng nào');
            $table->foreignId('road_id')->nullable()->constrained('roads')->onDelete('set null')->comment('Nằm trên tuyến đường nào');
            
            $table->string('code')->unique()->comment('Mã hố ga, VD: HGT-SK-01');
            $table->decimal('depth', 5, 2)->nullable()->comment('Độ sâu hố ga (m)');
            $table->decimal('elevation', 6, 2)->nullable()->comment('Cao độ đáy/nắp hố ga (m)');
            $table->string('pipe_diameter')->nullable()->comment('Kích thước/Đường kính ống đấu nối (mm)');
            $table->enum('status', ['active', 'maintenance', 'blocked', 'closed'])->default('active')->comment('Trạng thái hoạt động');
            $table->text('note')->nullable()->comment('Ghi chú kỹ thuật hoặc vị trí');
            $table->timestamps();
        });

        // Thêm cột tọa độ điểm Point (WGS84) cho Hố ga Nước thải
        DB::statement('ALTER TABLE wastewater_manholes ADD COLUMN geom geometry(Point, 4326);');


        // 2. BẢNG HỐ GA NƯỚC MƯA (Stormwater / Rainwater Manholes)
        Schema::create('rainwater_manholes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade')->comment('Thuộc Doanh nghiệp nào');
            $table->foreignId('factory_id')->nullable()->constrained('factories')->onDelete('cascade')->comment('Thuộc Nhà xưởng nào');
            $table->foreignId('road_id')->nullable()->constrained('roads')->onDelete('set null')->comment('Nằm trên tuyến đường nào');
            
            $table->string('code')->unique()->comment('Mã hố ga, VD: HGM-SK-01');
            $table->decimal('depth', 5, 2)->nullable()->comment('Độ sâu hố ga (m)');
            $table->decimal('elevation', 6, 2)->nullable()->comment('Cao độ đáy/nắp hố ga (m)');
            $table->string('cover_material')->nullable()->comment('Chất liệu nắp hố ga (Gang, Bê tông, Composite...)');
            $table->enum('status', ['active', 'maintenance', 'blocked', 'closed'])->default('active')->comment('Trạng thái hoạt động');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // Thêm cột tọa độ điểm Point (WGS84) cho Hố ga Nước mưa
        DB::statement('ALTER TABLE rainwater_manholes ADD COLUMN geom geometry(Point, 4326);');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rainwater_manholes');
        Schema::dropIfExists('wastewater_manholes');
    }
}
