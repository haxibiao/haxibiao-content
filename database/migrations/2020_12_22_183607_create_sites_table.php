<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSitesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('sites')) {
            return;
        }

        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->nullable()->index()->comment('站名');
            $table->string('domain', 50)->nullable()->index()->comment('域名');
            $table->string('theme')->nullable()->comment('主题');
            $table->string('title')->nullable();
            $table->string('keywords')->nullable();
            $table->string('description')->nullable();
            $table->json('json')->nullable()->comment('最近30天的百度索引量');
            $table->json('data')->nullable()->comment('json数据 附加信息');
            $table->string('icp')->nullable()->comment('icp备案信息');
            $table->boolean('active')->nullable()->comment('是否激活');
			$table->string('company')->nullable()->comment('公司实体');

            $table->string('owner', 20)->nullable()->comment('站长');
            $table->string('ziyuan_token')->nullable()->comment('百度资源站长提交收录的token');
            $table->string('toutiao_token', 30)->nullable()->comment('头条站长token');
            $table->string('360_token', 30)->nullable()->comment('360站长token');
            $table->string('sogou_token', 30)->nullable()->comment('搜狗站长token');
            $table->string('shenma_token', 30)->nullable()->comment('神马站长token');

            $table->string('shenma_owner_email', 50)->nullable()->comment('神马站长邮箱地址');

            $table->text('footer_js')->nullable()->comment('底部js 支持如ga，mta，matomo统计等站长权限');
            $table->string('verify_meta')->nullable()->comment('站长验证用meta');
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
        Schema::dropIfExists('sites');
    }
}
