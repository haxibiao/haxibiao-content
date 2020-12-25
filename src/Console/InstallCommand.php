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
        $this->vendorPublish();

        $this->comment("复制 stubs ...");
        $this->copyStubs();

        //FIXME: 暂时不强制同步App\Category，动静太大，可以在需要的场景用 不同namespace 下的 Category(区分 Content 和 Question包)
        // copy($this->resolveStubPath('/stubs/Category.stub'), app_path('Category.php'));

        $this->comment('迁移数据库变化...');
        $this->call('migrate');
    }

    public function copyStubs()
    {
        //复制所有app stubs
        foreach (glob(__DIR__ . '/stubs/*.stub') as $filepath) {
            $filename = basename($filepath);
            copy($filepath, app_path(str_replace(".stub", ".php", $filename)));
        }
        //复制所有nova stubs
        if (!is_dir(app_path('Nova'))) {
            mkdir(app_path('Nova'));
        }
        foreach (glob(__DIR__ . '/stubs/Nova/*.stub') as $filepath) {
            $filename = basename($filepath);
            copy($filepath, app_path('Nova/' . str_replace(".stub", ".php", $filename)));
        }
    }

    public function vendorPublish()
    {
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

        $this->call('vendor:publish', [
            '--tag'   => 'content-resources',
            '--force' => true,
        ]);
    }
}
