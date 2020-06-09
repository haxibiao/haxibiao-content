<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostRecommendsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('post_recommends')) {
            return;
        }

        Schema::create('post_recommends', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->unique()->comment('用户ID');
            $table->json('day_review_ids')->nullable()->comment("用户在各日期块的指针"); //用户各日期块的指针
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
        Schema::dropIfExists('post_recommends');
    }
}
