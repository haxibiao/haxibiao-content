<?php

namespace haxibiao\content;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class ContentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->bindPathsInContainer();

        $this->registerMorphMap();

        $this->mergeConfigFrom(
            __DIR__ . '/../config/haxibiao-categorized.php',
            'haxibiao-categorized'
        );

        $this->registerCommands();
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //安装时需要
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom($this->app->make('path.haxibiao-category.migrations'));

            $this->publishes([
                __DIR__ . '/../config/haxibiao-categorized.php' => config_path('haxibiao-categorized.php'),
            ], 'content-config');

            //发布 graphql
            $this->publishes([
                __DIR__ . '/../graphql' => base_path('graphql'),
            ], 'content-graphql');

            //发布 tests
            $this->publishes([
                __DIR__ . '/../tests' => base_path('tests'),
            ], 'content-tests');
        }

        $this->loadRoutesFrom(
            $this->app->make('path.haxibiao-category') . '/router.php'
        );
    }

    protected function bindPathsInContainer()
    {
        foreach ([
            'path.haxibiao-category'            => $root = dirname(__DIR__),
            'path.haxibiao-category.config'     => $root . '/config',
            'path.haxibiao-category.database'   => $database = $root . '/database',
            'path.haxibiao-category.migrations' => $database . '/migrations',
            'path.haxibiao-category.seeds'      => $database . '/seeds',
            'path.haxibiao-category.graphql'    => $database . '/graphql',
        ] as $abstract => $instance) {
            $this->app->instance($abstract, $instance);
        }
    }

    protected function registerCommands()
    {
        $this->commands([
            InstallCommand::class,
            CategoryReFactoringCommand::class,
        ]);
    }

    protected function registerMorphMap()
    {
        $this->morphMap([
            'categories' => 'haxibiao\content\Category',
        ]);
    }

    protected function morphMap(array $map = null, bool $merge = true): array
    {
        return Relation::morphMap($map, $merge);
    }
}
