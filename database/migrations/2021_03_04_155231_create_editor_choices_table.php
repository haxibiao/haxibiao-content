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
        if (Schema::hasTable('editor_choices')) {
            return;
        }
        Schema::create('editor_choices', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('标题');
            $table->string('summary')->nullable()->comment('描述');
            $table->string('editor_id')->nullable()->comment('小编id');
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
