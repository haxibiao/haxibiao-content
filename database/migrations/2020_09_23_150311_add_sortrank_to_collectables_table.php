<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSortrankToCollectablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('collectables', function (Blueprint $table) {
            if (!Schema::hasColumn('collections','sort_rank')){
                $table->unsignedInteger('sort_rank')->nullable()->comment('排序(置顶方法)');
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
        Schema::table('collectables', function (Blueprint $table) {
            //
        });
    }
}
