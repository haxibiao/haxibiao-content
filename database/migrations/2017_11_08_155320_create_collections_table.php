<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('collections')) {
            return;
        }
        Schema::create('collections', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->index();
            $table->integer('status')->default(\Haxibiao\Content\Collection::STATUS_ONLINE)->index();
            $table->string('type', 255)->default('article')->index();
            $table->string('name', 255);
            $table->string('description', 255)->nullable();
            $table->string('logo', 255)->nullable();
            $table->json('json')->nullable()->comment('非结构化的数据，冗余一些额外信息');
            $table->unsignedInteger('sort_rank')->nullable()->index()->comment('排序(置顶方法)');

            $table->integer('count')->default(0);
            $table->integer('count_words')->default(0);
            $table->integer('count_follows')->default(0);
            $table->unsignedInteger('count_posts')->default(0);
            $table->unsignedInteger('count_views')->default(0)->comment('浏览量');

            $table->string('collection_key', 50)->nullable()->index()->comment('合集的唯一key: ainicheng_1122');
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
