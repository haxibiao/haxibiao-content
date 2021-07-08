<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRankToSticksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sticks', function (Blueprint $table) {
            if (!Schema::hasColumn('sticks', 'rank')) {
                $table->integer('rank')->default(0)->comment('权重0-999，控制在页面的展示顺序');
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
