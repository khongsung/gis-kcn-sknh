<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateFactoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('factories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plot_id')->nullable()->constrained('plots')->onDelete('set null');
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('set null');
            $table->string('code')->unique()->comment('Mã nhà xưởng: CN-01-X1');
            $table->decimal('area')->nullable()->comment('Diện tích xưởng m2');
            $table->enum('status', ['available', 'rented', 'under_construction'])->default('available');
            $table->timestamps();
        });

        // Thêm cột Spatial Polygon cho Nhà xưởng
        DB::statement('ALTER TABLE factories ADD COLUMN geom geometry(Polygon, 4326);');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('factories');
    }
}
