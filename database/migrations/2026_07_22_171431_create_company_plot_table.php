<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyPlotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Tạo Bảng trung gian ràng buộc N - N
        Schema::create('company_plot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plot_id')->constrained('plots')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->decimal('leased_area', 12, 2)->nullable()->comment('Diện tích công ty thuê trên lô này');
            $table->date('lease_start_date')->nullable();
            $table->timestamps();

            // Đảm bảo 1 công ty không bị trùng lặp 2 lần trên cùng 1 lô đất
            $table->unique(['plot_id', 'company_id']);
        });

        // 2. (Tùy chọn) Bỏ cột company_id cũ trong bảng plots vì đã chuyển sang bảng trung gian
        if (Schema::hasColumn('plots', 'company_id')) {
            Schema::table('plots', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('company_plot');
    }
}
