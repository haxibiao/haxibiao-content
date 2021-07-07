<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEditorChoiceMovieTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('editor_choice_movie', function (Blueprint $table) {
            $table->id();
            $table->integer('editor_choice_id')->comment('精选id');
            $table->integer('movie_id')->comment('电影id');
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
        Schema::dropIfExists('editor_choice_movie');
    }
}
