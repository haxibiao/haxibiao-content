<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('categories')) {
            return;
        }

        Schema::create('categories', function (Blueprint $table) {
            $table->increments('id');

            $table->string('name', 255);
            $table->string('name_en', 255)->nullable()->comment('可以理解为分类的url 的 slug 部分');
            $table->integer('user_id')->index();
            $table->string('description', 255)->nullable();
            $table->string('logo', 255)->nullable();

            $table->string('type', 255)->nullable()->comment('类型如：article, video 方便过滤专注某类内容的分类时用');
            $table->integer('parent_id')->default(0);
            $table->boolean('has_child')->default(0);
            $table->integer('level')->nullable()->index();
            $table->integer('order')->nullable()->index()->comment('分类需要排序时用');
            $table->integer('status')->default(\Haxibiao\Content\Category::STATUS_DRAFT)->comment('0: 不让投稿, 1: allow');
            $table->integer('request_status')->default(0)->comment('0: 投稿无需审核, 1: need approve');

            $table->integer('new_requests')->default(0)->comment('新投稿数');
            $table->string('new_request_title', 255)->nullable()->comment('新投稿标题');
            $table->boolean('is_official')->default(0)->comment('APP:是否官方专题');
            $table->boolean('is_for_app')->default(0)->comment('APP:是否在APP首页显示');
            $table->string('logo_app', 255)->nullable()->comment('APP:APP美化后的LOGO图片');
            $table->unsignedInteger('questions_count')->default(0)->comment('问题统计');
            $table->unsignedInteger('answers_count')->default(0)->comment('回答统计');
            $table->json('ranks')->nullable()->comment('分类下题目的ranks');
            $table->integer('rank')->nullable();

            $table->tinyInteger('allow_submit')->default(1)->comment('系统是否允许用户出题到这个分类');
            $table->unsignedInteger('correct_answer_users_count')->default(0)->comment('答对用户');
            $table->unsignedInteger('min_answer_correct')->default(20)->index()->comment('最小答对数');
            $table->json('answers_count_by_month')->nullable()->comment('每个月的答题次数');
            $table->string('tips', 255)->nullable()->comment('分类tips');
            $table->string('icon', 255)->nullable();

            $table->integer('count_follows')->default(0)->index();
            $table->integer('count')->default(0)->comment('文章数');
            $table->integer('count_questions')->default(0);
            $table->integer('count_videos')->default(0)->comment('视频文章数');

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
        Schema::dropIfExists('categories');
    }
}
