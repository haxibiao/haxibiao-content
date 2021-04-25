<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveUserIdFromCollectables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('collectables', function (Blueprint $table) {

            //清理多余的字段引起的db出错
            if (Schema::hasColumn('collectables', 'user_id')) {
                $table->dropColumn('user_id');
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
