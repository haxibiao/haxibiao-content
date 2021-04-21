<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('articles')) {
            return;
        }
        Schema::create('articles', function (Blueprint $table) {
            $table->increments('id');

            $table->string('title', 255)->nullable()->index();
            $table->string('slug', 255)->nullable()->unique();
            $table->mediumText('description')->nullable();
            $table->string('keywords', 255)->nullable();
            $table->longText('body')->nullable();
            $table->string('image_top', 255)->nullable();
            $table->string('cover_path', 255)->nullable()->comment('文章的封面，图片或者视频的截图');
            $table->string('author', 255)->nullable();
            $table->integer('author_id')->nullable();
            $table->integer('user_id');
            $table->integer('status')->default(\Haxibiao\Content\Article::STATUS_REVIEW)->index();

            $table->integer('category_id')->nullable()->index();
            $table->integer('collection_id')->nullable()->index();
            $table->unsignedInteger('video_id')->nullable()->index()->comment('视频id');
            $table->unsignedInteger('product_id')->nullable()->index();
            $table->unsignedInteger('issue_id')->nullable()->comment('问题ID');

            $table->boolean('is_top')->default(0)->index();
            $table->string('source_url', 255)->nullable()->index();
            $table->string('remark', 255)->nullable()->comment('备注');
            $table->string('type', 10)->default('article')->index()->comment('内容的类型:article:普通文章，video:视频, post:动态');
            $table->tinyInteger('submit')->default(1)->index()->comment('审核状态: -1 已拒绝, 0 审核中, 1 已收录');
            $table->unsignedBigInteger('review_id')->nullable()->index();
            $table->boolean('is_hot')->default(0)->index();
            $table->text('json')->nullable();

            $table->integer('hits')->default(0);
            $table->integer('hits_mobile')->default(0);
            $table->integer('hits_phone')->default(0);
            $table->integer('hits_wechat')->default(0);
            $table->integer('hits_robot')->default(0);

            $table->integer('count_words')->default(0);
            $table->integer('count_replies')->default(0);
            $table->integer('count_favorites')->default(0);
            $table->integer('count_shares')->default(0);
            $table->integer('count_tips')->default(0);
            $table->integer('count_likes')->default(0)->index();
            $table->integer('count_comments')->nullable();
            $table->integer('count_reports')->default(0);
            $table->integer('count_follows')->default(0);

            $table->timestamp('delay_time')->nullable();
            $table->timestamp('commented')->nullable();
            $table->timestamp('edited_at')->nullable()->comment('最后编辑时间');
            $table->softDeletes();
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
        Schema::dropIfExists('articles');
    }
}
