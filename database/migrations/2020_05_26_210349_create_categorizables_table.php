<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\RenameColumn;
use Illuminate\Support\Facades\Schema;

class CreateCategorizablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('categorizeds')) {
            Schema::rename('categorizeds', 'categorizables');
            //重构旧的表结构
            Schema::table('categorizables', function (Blueprint $table) {
                if (Schema::hasColumn('categorizables', 'categorized_type')) {
                    $table->renameColumn('categorized_type', 'categorizable_type');
                    $table->renameColumn('categorized_id', 'categorizable_id');
                }
            });
            return;
        }

        Schema::create('categorizables', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('category_id');
            $table->morphs('categorizable');
            $table->string('submit')->nullable()->index()->comment('投稿状态：待审核，已收录，已拒绝');
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
        Schema::dropIfExists('categorizeds');
        Schema::dropIfExists('categorizables');
    }
}
