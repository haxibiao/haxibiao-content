<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCollectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->index();
            $table->integer('status')->default(1)->index();  // 0 private 1 public
            $table->string('type')->default('article')->index(); // faved...
            $table->string('name');
            $table->string('description');
            $table->string('logo')->nullable();
            $table->json('json')->nullable()->comment('非结构化的数据，冗余一些额外信息');
            $table->unsignedInteger('sort_rank')->nullable()->index()->comment('排序(置顶方法)');

            //add counts
            $table->integer('count')->default(0);
            $table->integer('count_words')->default(0);
            $table->integer('count_follows')->default(0)->index();
            $table->unsignedInteger('count_posts')->default(0)->index();

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
        Schema::dropIfExists('collections');
    }
}
