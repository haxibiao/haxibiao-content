<?php

namespace Haxibiao\Content\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class InstallCommand extends Command
{

    /**
     * The name and signature of the Console command.
     *
     * @var string
     */
    protected $signature = 'content:install {--force : 强制全新安装}';

    /**
     * The Console command description.
     *
     * @var string
     */
    protected $description = '安装 haxibiao/content';

    /**
     * Execute the Console command.
     *
     * @return void
     */
    public function handle()
    {
        $force = $this->option('force');

        $this->info('发布 content 资源 ...');
        $this->call('content:publish', ['--force' => $force]);

        $this->comment("复制 content stubs ...");
        copyStubs(__DIR__, $force);

        //FIXME: 为啥不敢install的时候提供 App/Category 基于 Haxibiao\Content\Category?
        // 新答题产品里的category字段有差别，haxibiao/question里通过migrate修复结构
        // 通过playWithQuestion补充即可，重构question包时，先兼容并基于content系统

        $this->comment('迁移 content 数据库变化...');
        $this->call('migrate');
    }
}
