<?php

use Haxibiao\Content\Stickable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddChannelToStickables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('stickables')){
            return;
        }
        Schema::table('stickables', function (Blueprint $table) {
            if(!Schema::hasColumn('stickables','channel')){
                $table->string('channel',10)->default(Stickable::CHANNEL_OF_PC)
                    ->comment('频道：App or WEB');
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
        Schema::table('stickables', function (Blueprint $table) {
            //
        });
    }
}
