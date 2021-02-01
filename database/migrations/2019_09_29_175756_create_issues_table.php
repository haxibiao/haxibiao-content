<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIssuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //web专用付费问答
        Schema::create('issues', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id')->index();
            $table->string('title', 255);
            $table->text('background')->nullable();
            $table->unsignedInteger('latest_solution_id')->nullable();
            $table->unsignedInteger('best_solution_id')->nullable();
            $table->unsignedInteger('gold')->default(0)->comment('金币');
            $table->boolean('is_anonymous')->default(0)->comment('是否匿名问答');
            $table->decimal('bonus')->nullable();
            $table->smallInteger('deadline')->nullable();
            $table->unsignedInteger('hits')->default(0);

            $table->unsignedInteger('count_answers')->default(0);
            $table->integer('count_favorites')->default(0);
            $table->integer('count_reports')->default(0);
            $table->integer('count_likes')->default(0);

            $table->boolean('closed')->default(0)->index()->comment('问题是否解决');
            $table->string('image1', 255)->nullable();
            $table->string('image2', 255)->nullable();
            $table->string('image3', 255)->nullable();
            $table->string('solution_ids', 255)->nullable();

            $table->unsignedInteger('gold')->default(0)->comment('金币');
            $table->boolean('is_pay')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('issues');
    }
}
