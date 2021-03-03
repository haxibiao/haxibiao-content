<?php

use Haxibiao\Content\Stickable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStickablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //重构自秀儿的sticks
//        Schema::dropIfExists('stickables');
        //重新整理置顶数据结构
         if (Schema::hasTable('stickables')) {
             return;
         }

        Schema::create('stickables', function (Blueprint $table) {
            $table->id();

            /**
             * morph item(stickable)
             * stickable_type: 类型：articles 图文｜videos 短视频｜movies 电影
             * stickable_id  : 内容id
             */
            $table->morphs('stickable');

            //site has many stickable items by name = by page + by area ...
            $table->tinyInteger('site_id')->nullable()->comment('站群站点');

            //pivot columns
            $table->string('name')->comment('按名称检索：首页轮播| 置顶电影 | 最新韩剧 | 经典韩剧 | 电影顶楼的推荐');
            $table->string('page')->nullable()->comment('按页面检索：首页 | 发现 | 视频 | 电影频道...');
            $table->string('area')->nullable()->comment('按位置减速：上下左右，需要配合页面检索');

            $table->string('channel',10)->default(Stickable::CHANNEL_OF_PC)
                ->comment('频道：APP or WEB');
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
        Schema::dropIfExists('stickables');
    }
}
