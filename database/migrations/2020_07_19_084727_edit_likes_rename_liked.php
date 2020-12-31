<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EditLikesRenameLiked extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('likes', function (Blueprint $table) {
            if (Schema::hasColumn('likes', 'liked_id') && !Schema::hasColumn('likes', 'likable_id')) {
                $table->renameColumn('liked_id', 'likable_id');
            }
            if (Schema::hasColumn('likes', 'liked_type') && !Schema::hasColumn('likes', 'likable_type')) {
                $table->renameColumn('liked_type', 'likable_type');
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
        Schema::table('likes', function (Blueprint $table) {
            //
        });
    }
}
