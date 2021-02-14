<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddScopeToPostRecommends extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('post_recommends', function (Blueprint $table) {
            if (!Schema::hasColumn('post_recommends', 'scope')) {
                $table->string('scope', 20)->nullable()->index()->comment('推荐类型');

                //用户可以有多个推荐范围了
                $table->dropUnique('post_recommends_user_id_unique');

                //重新简单索引user_id
                $table->index('user_id');
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
        Schema::table('post_recommends', function (Blueprint $table) {
            //
        });
    }
}
