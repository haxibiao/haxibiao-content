<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSticksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('sticks')) {
            return;
        }
        Schema::create('sticks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('site_id')->nullable()->index()->comment('站点ID,如果为null则为通用');
            $table->string('app_name', 30)->nullable()->index()->comment('App名字,如果为null则为通用');

            $table->unsignedInteger('editor_choice_id')->comment('编辑精选ID');
            $table->string('place', 30)->comment('应用场景｜置顶位置');
            $table->string('cover')->nullable()->comment('置顶封面');
            $table->unsignedInteger('editor_id')->index()->comment('编辑人员ID||谁置顶的');
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
        Schema::dropIfExists('sticks');
    }
}
