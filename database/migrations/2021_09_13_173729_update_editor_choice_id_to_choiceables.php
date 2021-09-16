<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateEditorChoiceIdToChoiceables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('choiceables', function (Blueprint $table) {
            //
            if (Schema::hasColumn('choiceables', 'editor_choice_id')) {
                $table->integer('editor_choice_id')->index()->comment('精选id')->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('choiceables', function (Blueprint $table) {
            //
        });
    }
}
