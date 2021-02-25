<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrafficTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //不兼容古老版本的traffic表
        Schema::dropIfExists('traffic');

        Schema::create('traffic', function (Blueprint $table) {
            $table->id();
            $table->string('url', 100)->nullable()->index()->comment('URL');
            $table->string('domain', 50)->nullable()->index()->comment('站点域名');
            $table->string('bot', 20)->nullable()->index()->comment('爬虫名称');
            $table->string('referer')->nullable()->index()->comment('搜索来源URL');
            $table->string('engine', 20)->nullable()->index()->comment('搜索引擎');
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
        Schema::dropIfExists('traffic');
    }
}
