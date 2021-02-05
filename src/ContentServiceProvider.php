<?php

namespace Haxibiao\Content;

use Haxibiao\Content\Console\NovelPush;
use Haxibiao\Content\Console\NovelSync;
use Illuminate\Support\ServiceProvider;
use Haxibiao\Content\Console\FixContent;
use Haxibiao\Content\Console\RefactorPost;
use Illuminate\Console\Scheduling\Schedule;
use Haxibiao\Content\Console\InstallCommand;
use Haxibiao\Content\Console\PublishCommand;
use Haxibiao\Content\Console\CrawlCollection;
use Haxibiao\Content\Console\ImportCollections;
use Haxibiao\Content\Console\SyncPostWithMovie;
use Haxibiao\Content\Console\RefactorCollection;
use Haxibiao\Content\Console\RefactorCategorizable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Haxibiao\Content\Console\StatisticVideoViewsCommand;

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
        foreach (glob($src_path . '/Helper/*.php') as $filename) {
            require_once $filename;
        }

        $this->bindPathsInContainer();

        $this->registerMorphMap();

        $this->mergeConfigFrom(
            __DIR__ . '/../config/content.php',
            'content'
        );

        $this->commands([
            InstallCommand::class,
            PublishCommand::class,
            NovelPush::class,
            NovelSync::class,
            RefactorCategorizable::class,
            RefactorPost::class,
            RefactorCollection::class,
            StatisticVideoViewsCommand::class,
            CrawlCollection::class,
            FixContent::class,
            ImportCollections::class,
            SyncPostWithMovie::class,
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

        if (!app()->configurationIsCached()) {
            $this->mergeConfigFrom(__DIR__ . '/../config/database.php', 'database.connections');
        }

        //安装时需要
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom($this->app->make('path.haxibiao-content.migrations'));

            $this->publishes([
                __DIR__ . '/../config/content.php' => config_path('content.php'),
            ], 'content-config');

            //发布 graphql
            $this->publishes([
                __DIR__ . '/../graphql' => base_path('graphql'),
            ], 'content-graphql');

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
//        \Haxibiao\Media\Spider::observe(Observers\SpiderObserver::class);
//        \Haxibiao\Media\Video::observe(Observers\VideoObserver::class);
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
            'questions'  => \App\Question::class,
        ]);
    }

    protected function morphMap(array $map = null, bool $merge = true): array
    {
        return Relation::morphMap($map, $merge);
    }
}
