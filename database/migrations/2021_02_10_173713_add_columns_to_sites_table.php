<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToSitesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sites', function (Blueprint $table) {
            if (!Schema::hasColumn('sites', 'company')) {
                $table->string('company')->nullable()->comment('公司实体');
            }

            //修复旧站点缺少的字段
            if (!Schema::hasColumn('sites', 'active')) {
                $table->boolean('active')->nullable()->comment('是否激活');
            }
            if (!Schema::hasColumn('sites', 'icp')) {
                $table->string('icp')->nullable()->comment('icp备案信息');
            }

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sites', function (Blueprint $table) {
            //
        });
    }
}
