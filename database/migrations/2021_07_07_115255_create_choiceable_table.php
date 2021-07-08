<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChoiceableTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('choiceables')) {
            return;
        }
        Schema::create('choiceables', function (Blueprint $table) {
            $table->id();
            $table->integer('editor_choice_id')->comment('精选id');
            $table->morphs('choiceable');
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
        Schema::dropIfExists('choiceables');
    }
}
