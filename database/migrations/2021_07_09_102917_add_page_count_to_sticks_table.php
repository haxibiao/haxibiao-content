<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPageCountToSticksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sticks', function (Blueprint $table) {
            //
            if (!Schema::hasColumn('sticks', 'page_count')) {
                $table->integer('page_count')->default(9)->comment('每页显示数量（动态控制前端展示）');
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
        Schema::table('sticks', function (Blueprint $table) {
            //
        });
    }
}
