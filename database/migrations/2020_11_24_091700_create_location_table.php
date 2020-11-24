<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('post_id')->comment('关联的post');
            $table->string('address')->comment('详细地址');
            $table->string('description')->comment('大概地址描述');
            $table->double('longitude',9,6)->comment('精度');
            $table->double('latitude',8,6)->comment('精度');
            $table->string('geo_code')->index()->comment('geohash code');

            $table->index(['longitude','latitude'],'lon_lat');
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
        Schema::dropIfExists('locations');
    }
}
