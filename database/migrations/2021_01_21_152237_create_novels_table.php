<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNovelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('novels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->index();
            $table->string('introduction')->nullable();
            $table->string('cover')->nullable();
            $table->string('author')->nullable();
            $table->unsignedInteger('words')->nullable();
            $table->tinyInteger('status')->index()->default(0);
            $table->unsignedInteger('category_id')->nullable()->index();
            $table->unsignedInteger('count_user')->default(0);
            $table->unsignedInteger('rank')->nullable()->index();
            $table->boolean('is_over')->default(false)->index();
            $table->unsignedInteger('count_read')->default(0);
            $table->unsignedInteger('count_chapter')->default(0);
            $table->string('source')->nullable();
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
        Schema::dropIfExists('novels');
    }
}
