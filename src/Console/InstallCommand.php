<?php

namespace haxibiao\content;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class InstallCommand extends Command
{

    /**
     * The name and signature of the Console command.
     *
     * @var string
     */
    protected $signature = 'content:install';

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
        $this->info('强制发布资源');

        $this->call('vendor:publish', [
            '--tag'   => 'content-config',
            '--force' => true,
        ]);
        $this->call('vendor:publish', [
            '--tag'   => 'content-graphql',
            '--force' => true,
        ]);

        $this->call('vendor:publish', [
            '--tag'   => 'content-nova',
            '--force' => true,
        ]);

        $this->call('vendor:publish', [
            '--tag'   => 'content-tests',
            '--force' => true,
        ]);

        $this->call('vendor:publish', [
            '--tag'   => 'content-factories',
            '--force' => true,
        ]);

        $this->comment("复制 stubs ...");
        copy(__DIR__ . '/stubs/Post.stub', app_path('Post.php'));
        copy(__DIR__ . '/stubs/PostRecommend.stub', app_path('PostRecommend.php'));

        // copy($this->resolveStubPath('/stubs/Category.stub'), app_path('Category.php'));

        $this->comment('迁移数据库变化...');
        $this->call('migrate');

    }

}
