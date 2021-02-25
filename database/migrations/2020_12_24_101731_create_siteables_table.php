<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSiteablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (Schema::hasTable('siteables')) {
            return;
        }

        Schema::create('siteables', function (Blueprint $table) {
            $table->id();
            $table->integer('site_id');
            $table->morphs('siteable');
            $table->timestamp('baidu_pushed_at')->nullable()->index()->comment('最后百度收录提交成功的时间');
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
        Schema::dropIfExists('siteables');
    }
}
