<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecruitmentNewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recruitment_news', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('title')->comment('Vị trí tuyển dụng');
            $table->integer('quantity')->default(1)->comment('Số lượng cần tuyển');
            $table->string('salary_range')->comment('VD: 10 - 15 triệu');
            $table->text('requirements')->nullable();
            $table->string('contact_info')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('deadline')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('recruitment_news');
    }
}
