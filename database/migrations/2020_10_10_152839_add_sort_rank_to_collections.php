<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSortRankToCollections extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('collections', function (Blueprint $table) {
            //添加集合排序字段
            Schema::table('collections', function (Blueprint $table) {
                if (!Schema::hasColumn('colletions', 'sort_rank')) {
                    $table->unsignedInteger('sort_rank')->nullable()->comment('排序(置顶方法)');
                }
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('colletions', function (Blueprint $table) {
            //
        });
    }
}
