<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToTaggables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('taggables', function (Blueprint $table) {
            if (!Schema::hasColumn('taggables', 'id')) {
                $table->increments('id');
            }
            if (!Schema::hasColumn('taggables', 'user_id')) {
                $table->integer('user_id')->unsigned();
            }
            if (!Schema::hasColumn('taggables', 'created_at')) {
                $table->timestamps();
            }
            if (!Schema::hasColumn('taggables', 'tag_name')) {
                $table->string('tag_name');
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
        Schema::table('taggables', function (Blueprint $table) {
            //
        });
    }
}
