<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('posts')) {
            return;
        }

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->index()->comment('展示用户');
            $table->unsignedInteger('owner_id')->index()->nullable()->comment('所有者');
            $table->unsignedInteger('video_id')->nullable()->index()->comment('视频ID');
            $table->integer('spider_id')->index()->nullable()->comment('动态的爬虫id');

            $table->string('description')->nullable()->comment('描述');
            $table->text('content')->nullable()->comment('内容');

            $table->tinyInteger('status')->default(0)->comment('状态');

            $table->unsignedInteger('question_id')->nullable()->comment('关联题目');
            $table->unsignedInteger('movie_id')->nullable()->comment('关联长视频');

            $table->unsignedInteger('hot')->default(0)->comment('热度');
            $table->unsignedInteger('count_likes')->default(0)->comment('点赞数');
            $table->unsignedInteger('count_comments')->default(0)->comment('点赞数');

            $table->unsignedBigInteger('review_id')->index()->nullable()->comment('快速排重推荐用');
            $table->unsignedBigInteger('review_day')->default(0)->index()->comment('快速排重推荐用');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('posts');
    }
}
