<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollectablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('collectables')) {
            Schema::drop('collectables');
        }
        Schema::create('collectables', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer('collection_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->morphs('collectable');
            $table->string('collection_name');

            //索引字段
            $table->unique(['collection_id', 'collectable_id', 'collectable_type'],'collectable_unique');
            $table->index('user_id');
            $table->index('collection_name');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('collectable');
    }
}
