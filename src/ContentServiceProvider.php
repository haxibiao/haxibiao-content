<?php

namespace Haxibiao\Content;

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

        $this->commands([
            InstallCommand::class,
            CategoryReFactoringCommand::class,
            // FixContent::class,
        ]);
    }

    /**
     * Bootstrap services.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
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
                __DIR__ . '/../graphql/post' => base_path('graphql/post'),
            ], 'content-graphql');

            $this->publishes([
                __DIR__ . '/../graphql/favorite' => base_path('graphql/favorite'),
            ], 'content-graphql');

            // 发布 Nova
            $this->publishes([
                __DIR__ . '/Nova' => base_path('app/Nova'),
            ], 'content-nova');

            //发布 tests
            $this->publishes([
                __DIR__ . '/../tests/Feature/GraphQL/Post'         => base_path('tests/Feature/GraphQL/Post'),
                __DIR__ . '/../tests/Feature/GraphQL/PostTest.php' => base_path('tests/Feature/GraphQL/PostTest.php'),
            ], 'content-tests');

            //发布 factories
            $this->publishes([
                __DIR__ . '/../database/factories/PostFactory.php'  => base_path('database/factories/PostFactory.php'),
                __DIR__ . '/../database/factories/VideoFactory.php' => base_path('database/factories/VideoFactory.php'),
            ], 'content-factories');
        }

        $this->loadRoutesFrom(
            $this->app->make('path.haxibiao-category') . '/router.php'
        );

        //绑定observers
        \Haxibiao\Media\Spider::observe(Observers\SpiderObserver::class);
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

    protected function registerMorphMap()
    {
        $this->morphMap([
            'categories' => 'Haxibiao\Content\Category',
        ]);
    }

    protected function morphMap(array $map = null, bool $merge = true): array
    {
        return Relation::morphMap($map, $merge);
    }
}
