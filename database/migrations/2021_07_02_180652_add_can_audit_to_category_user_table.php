<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCanAuditToCategoryUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('category_user', function (Blueprint $table) {
            //
            if (!Schema::hasColumn('category_user', 'can_audit')) {
                $table->boolean('can_audit')->default(false)->comment('用户在该题库的审题权限');
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
        Schema::table('category_user', function (Blueprint $table) {
            //
        });
    }
}
