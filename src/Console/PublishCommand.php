<?php

namespace Haxibiao\Content\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class PublishCommand extends Command
{

    /**
     * The name and signature of the Console command.
     *
     * @var string
     */
    protected $signature = 'content:publish {--force}';

    /**
     * The Console command description.
     *
     * @var string
     */
    protected $description = '发布 haxibiao/content';

    /**
     * Execute the Console command.
     *
     * @return void
     */
    public function handle()
    {
        $force = $this->option('force');

        $this->comment("发布 content");
        $this->call('vendor:publish', ['--provider' => 'Haxibiao\Content\ContentServiceProvider', '--force' => $force]);

        $this->call('vendor:publish', [
            '--tag'   => 'cms-config',
            '--force' => $this->option('force'),
        ]);

        $this->call('vendor:publish', [
            '--tag'   => 'cms-resources',
            '--force' => $this->option('force'),
        ]);
    }

}
