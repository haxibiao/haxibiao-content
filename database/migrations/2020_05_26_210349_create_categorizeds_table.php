<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategorizedsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('categorizeds')) {
            return;
        }

        Schema::create('categorizeds', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('category_id');
            $table->morphs('categorized');
            $table->string('submit')->nullable()->index()->comment('投稿状态：待审核，已收录，已拒绝');
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
        Schema::dropIfExists('categorizables');
    }
}
