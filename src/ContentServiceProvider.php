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
        //帮助函数
        $src_path = __DIR__;
        foreach (glob($src_path . '/helpers/*.php') as $filename) {
            require_once $filename;
        }

        $this->bindPathsInContainer();

        $this->registerMorphMap();

        $this->mergeConfigFrom(
            __DIR__ . '/../config/content.php',
            'content'
        );

        $this->commands([
            Console\InstallCommand::class,
            Console\RefactorCategorizable::class,
            Console\RefactorPost::class,
            Console\RefactorCollection::class,
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
        $enabled = config('media.enabled_statistics_video_views', false);
        if ($enabled) {
            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);
                $schedule->command('haxibiao:statistic:video_viewers')->dailyAt('2:30');;
            });
        }

        //安装时需要
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom($this->app->make('path.haxibiao-content.migrations'));

            $this->publishes([
                __DIR__ . '/../config/content.php' => config_path('content.php'),
            ], 'content-config');

            //发布 graphql
            $this->publishes([
                __DIR__ . '/../graphql/category'    => base_path('graphql/category'),
                __DIR__ . '/../graphql/collection'  => base_path('graphql/collection'),
                __DIR__ . '/../graphql/tag'         => base_path('graphql/tag'),
                __DIR__ . '/../graphql/article'     => base_path('graphql/article'),
                __DIR__ . '/../graphql/post'        => base_path('graphql/post'),
                __DIR__ . '/../graphql/issue'       => base_path('graphql/issue'),
                __DIR__ . '/../graphql/issueInvite' => base_path('graphql/issueInvite'),
                __DIR__ . '/../graphql/location'    => base_path('graphql/location'),
                __DIR__ . '/../graphql/solution'    => base_path('graphql/solution'),
            ], 'content-graphql');

            // 发布 Nova
            $this->publishes([
                __DIR__ . '/Nova' => base_path('app/Nova'),
            ], 'content-nova');

            //不发布tests代码，可以直接在包下UT

            //发布 resoucre
            $this->publishes([
                __DIR__ . '/../resources/css'    => base_path('public/css'),
                __DIR__ . '/../resources/images' => base_path('public/images'),
                __DIR__ . '/../resources/js'     => base_path('public/js'),
                // __DIR__ . '/../resources/views'  => base_path('resources/views'),
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
            'categories' => \App\Category::class,
            'articles'   => \App\Article::class,
            'posts'      => \App\Post::class,
            'issues'     => \App\Issue::class,
        ]);
    }

    protected function morphMap(array $map = null, bool $merge = true): array
    {
        return Relation::morphMap($map, $merge);
    }
}
