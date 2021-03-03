<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEditorChoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(Schema::hasTable('editor_choices')){
            return;
        }
        Schema::create('editor_choices', function (Blueprint $table) {
            $table->id();

            $table->string('title')->nullable()->comment('标题');
            $table->string('description')->nullable()->comment('副标题或描述');
            $table->string('image_url')->nullable()->comment('封面图片');
            /**
             * 存放精选的内容示例：
             *  {"movies":{1,2,3,4}}
             *  movies:表示精选的资源类型，后面是该种类型资源的ID。
             */
            $table->json('data')->nullable()->comment('附加信息');

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
        Schema::dropIfExists('editor_choices');
    }
}
