<?php

namespace Haxibiao\Content;

use Illuminate\Console\Scheduling\Schedule;
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
            __DIR__ . '/../config/haxibiao-content.php',
            'haxibiao-content'
        );

        $this->commands([
            Console\InstallCommand::class,
            Console\CategoryReFactoringCommand::class,
            Console\PostReFactoringCommand::class,
            Console\CollectionReFactoringCommand::class,
            Console\StatisticVideoViewsCommand::class,
            Console\CrawlCollection::class,
            
        ]);
    }

    /**
     * Bootstrap services.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function boot()
    {
        // 更新每日播放量
        $enabled = config('media.enabled_statistics_video_views',false);
        if($enabled){
            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);
                $schedule->command('haxibiao:statistic:video_viewers')->dailyAt('2:30');;
            });
        }

        //安装时需要
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom($this->app->make('path.haxibiao-content.migrations'));

            $this->publishes([
                __DIR__ . '/../config/haxibiao-content.php' => config_path('haxibiao-content.php'),
            ], 'content-config');

            //发布 graphql
            $this->publishes([
                __DIR__ . '/../graphql/post' => base_path('graphql/post'),
            ], 'content-graphql');

            $this->publishes([
                __DIR__ . '/../graphql/article' => base_path('graphql/article'),
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

            //发布 resoucre
            $this->publishes([
                __DIR__ . '/../resources/css'  => base_path('public/css'),
                __DIR__ . '/../resources/images'  => base_path('public/images'),
                __DIR__ . '/../resources/js'  => base_path('public/js'),
                __DIR__ . '/../resources/views'  => base_path('resources/views'),
            ], 'content-resources');
        }

        $this->loadRoutesFrom(
            $this->app->make('path.haxibiao-content') . '/router.php'
        );

        //绑定observers
        \Haxibiao\Media\Spider::observe(Observers\SpiderObserver::class);
        \Haxibiao\Media\Video::observe(Observers\VideoObserver::class);
    }

    protected function bindPathsInContainer()
    {
        foreach ([
            'path.haxibiao-content'            => $root = dirname(__DIR__),
            'path.haxibiao-content.config'     => $root . '/config',
            'path.haxibiao-content.database'   => $database = $root . '/database',
            'path.haxibiao-content.migrations' => $database . '/migrations',
            'path.haxibiao-content.seeds'      => $database . '/seeds',
            'path.haxibiao-content.graphql'    => $root . '/graphql',
        ] as $abstract => $instance) {
            $this->app->instance($abstract, $instance);
        }
    }

    protected function registerMorphMap()
    {
        $this->morphMap([
            'categories' => config('haxibiao-content.models.category'),
            'articles'   => config('haxibiao-content.models.article'),
            'posts'      => config('haxibiao-content.models.post'),
            'issues'      => config('haxibiao-content.models.issue'),
        ]);
    }

    protected function morphMap(array $map = null, bool $merge = true): array
    {
        return Relation::morphMap($map, $merge);
    }
}
