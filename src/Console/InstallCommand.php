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

        $this->comment("复制 stubs ...");

        copy($this->resolveStubPath('/stubs/Category.stub'), app_path('Category.php'));

    }

}
