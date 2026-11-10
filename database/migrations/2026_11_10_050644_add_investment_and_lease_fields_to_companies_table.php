<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInvestmentAndLeaseFieldsToCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            // Tổng diện tích thuê đất (m2)
            $table->decimal('total_leased_area', 12, 2)->nullable()->after('tax_code')
                  ->comment('Tổng diện tích thuê theo GCNĐT hoặc HĐ nguyên tắc (m2)');

            // Giấy chứng nhận đầu tư (IRC)
            $table->string('investment_certificate_number', 100)->nullable()->after('total_leased_area')
                  ->comment('Số Giấy chứng nhận đăng ký đầu tư (IRC)');
            $table->date('investment_certificate_date')->nullable()->after('investment_certificate_number')
                  ->comment('Ngày cấp GCNĐT');

            // Thời gian thuê đất / Thời hạn hoạt động dự án
            $table->date('lease_start_date')->nullable()->after('investment_certificate_date')
                  ->comment('Ngày bắt đầu thời hạn thuê / chấp thuận đầu tư');
            $table->date('lease_end_date')->nullable()->after('lease_start_date')
                  ->comment('Ngày hết hạn thuê / hết thời hạn dự án');

            // Ngành nghề chính & Quốc gia xuất xứ vốn
            $table->string('main_industry', 150)->nullable()->after('industry')
                  ->comment('Ngành nghề sản xuất chính');
            $table->string('country', 100)->default('Việt Nam')->after('main_industry')
                  ->comment('Quốc gia / Vùng lãnh thổ chủ quản (Hàn Quốc, Trung Quốc, Việt Nam...)');

            // Ghi chú nội bộ
            $table->text('notes')->nullable()->after('description')
                  ->comment('Ghi chú vận hành, tình trạng pháp lý, môi trường');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'total_leased_area',
                'investment_certificate_number',
                'investment_certificate_date',
                'lease_start_date',
                'lease_end_date',
                'main_industry',
                'country',
                'notes',
            ]);
        });
    }
}
