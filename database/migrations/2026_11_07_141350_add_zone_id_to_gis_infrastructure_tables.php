<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddZoneIdToGisInfrastructureTables extends Migration
{
    /**
     * Danh sách 9 bảng hạ tầng cần thêm zone_id
     */
    protected array $targetTables = [
        'plots',
        'companies',
        'factories',
        'water_pipes',
        'substations',
        'wastewater_manholes',
        'rainwater_manholes',
        'greenery',
        'water_bodies',
    ];

    public function up()
    {

        // 1. Thêm cột center_geom (Point 4326) vào bảng industrial_zones nếu chưa có
        if (Schema::hasTable('industrial_zones')) {
            $hasGeom = DB::selectOne("
                SELECT column_name 
                FROM information_schema.columns 
                WHERE table_name = 'industrial_zones' AND column_name = 'center_geom'
            ");

            if (!$hasGeom) {
                // Thêm cột geometry Point SRID 4326
                DB::statement("SELECT AddGeometryColumn('industrial_zones', 'center_geom', 4326, 'POINT', 2)");
                
                // Cập nhật tọa độ mặc định cho KCN Song Khê - Nội Hoàng (Lat: 21.2482, Lng: 106.1765)
                DB::statement("
                    UPDATE industrial_zones 
                    SET center_geom = ST_SetSRID(ST_Point(106.1765, 21.2482), 4326)
                    WHERE center_geom IS NULL
                ");
            }
        }

        // 1. Lấy ID của KCN Song Khê - Nội Hoàng (tìm theo mã 'SK_NH' hoặc tên)
        $sknhZone = DB::table('industrial_zones')
            ->where('code', 'SK_NH')
            ->orWhere('name', 'LIKE', '%Song Khê%')
            ->first();

        // 2. Duyệt qua từng bảng để tạo cột zone_id nullable, thêm khóa ngoại và index
        foreach ($this->targetTables as $tableName) {
            if (Schema::hasTable($tableName)) {
                
                // Thêm cột zone_id nếu chưa có
                if (!Schema::hasColumn($tableName, 'zone_id')) {
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->foreignId('zone_id')
                              ->nullable()
                              ->after('id')
                              ->constrained('industrial_zones')
                              ->nullOnDelete();

                        // Đánh Index cho zone_id để tối ưu tốc độ truy vấn WHERE zone_id = :id
                        $table->index('zone_id');
                    });
                }

                // 3. Cập nhật toàn bộ dữ liệu cũ về ID của KCN Song Khê - Nội Hoàng
                if ($sknhZone) {
                    DB::table($tableName)
                        ->whereNull('zone_id')
                        ->update(['zone_id' => $sknhZone->id]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Rollback an toàn: Xóa khóa ngoại, xóa index và xóa cột zone_id
        foreach ($this->targetTables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'zone_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->dropForeign(["{$tableName}_zone_id_foreign"]);
                    $table->dropIndex(["{$tableName}_zone_id_index"]);
                    $table->dropColumn('zone_id');
                });
            }
        }
    }
}
