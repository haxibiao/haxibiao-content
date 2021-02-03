<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('tags')) {
            return;
        }

        Schema::create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->comment('标签名'); //FIXME: 这name应该是unique
            $table->unsignedInteger('tag_id')->nullable()->index()->comment('标签ID'); //FIXME: 这个字段应该是冗余的
            $table->unsignedInteger('user_id')->nullable()->index()->comment('用户ID');
            $table->integer('type')->default(0)->index();
            $table->unsignedInteger('count')->default(0)->comment('总数');
            $table->tinyInteger('status')->default(0)->comment('状态: -1:删除 0:默认 1:优先');
            $table->integer('rank')->default(0)->comment('排名');
            $table->string('remark')->nullable()->comment('描述');
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
        Schema::dropIfExists('tags');
    }
}
