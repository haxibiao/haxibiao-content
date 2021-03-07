<?php

namespace Haxibiao\Content;

use Haxibiao\Content\Console\ArticleClear;
use Haxibiao\Content\Console\CrawlCollection;
use Haxibiao\Content\Console\FixContent;
use Haxibiao\Content\Console\ImportCollections;
use Haxibiao\Content\Console\InstallCommand;
use Haxibiao\Content\Console\NovelPush;
use Haxibiao\Content\Console\NovelSync;
use Haxibiao\Content\Console\PublishCommand;
use Haxibiao\Content\Console\RefactorCategorizable;
use Haxibiao\Content\Console\RefactorCollection;
use Haxibiao\Content\Console\RefactorPost;
use Haxibiao\Content\Console\StatisticVideoViewsCommand;
use Haxibiao\Content\Console\SyncPostWithMovie;
use Haxibiao\Content\Http\Middleware\SeoTraffic;
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
            ArticleClear::class,
            RefactorCategorizable::class,
            RefactorPost::class,
            RefactorCollection::class,
            StatisticVideoViewsCommand::class,
            CrawlCollection::class,
            FixContent::class,
            ImportCollections::class,
            SyncPostWithMovie::class,

            Console\Cms\SitemapGenerate::class,
            Console\Cms\ArchiveTraffic::class,
            Console\Cms\SeoWorker::class,
            Console\Cms\CmsUpdate::class,
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
            $this->mergeConfigFrom(__DIR__ . '/../config/cms.php', 'cms');
        }

        //安装时需要
        if ($this->app->runningInConsole()) {
            // FIXME:临时添加了一个属性动态控制了migrations的加载。
            if (config('content.migration_autoload')) {
                $this->loadMigrationsFrom($this->app->make('path.haxibiao-content.migrations'));
            }

            $this->publishes([
                __DIR__ . '/../config/content.php' => config_path('content.php'),
            ], 'content-config');

            $this->publishes([
                __DIR__ . '/../config/cms.php' => config_path('cms.php'),
            ], 'content-config');

            //发布 graphql
            $this->publishes([
                __DIR__ . '/../graphql' => base_path('graphql/content'),
            ], 'content-graphql');

            //发布 resoucre
            $this->publishes([
                __DIR__ . '/../resources/css'    => base_path('public/css'),
                __DIR__ . '/../resources/images' => base_path('public/images'),
                __DIR__ . '/../resources/js'     => base_path('public/js'),
                __DIR__ . '/../resources/views'  => base_path('resources/views'),
            ], 'content-resources');
        }

        //中间件
        app('router')->pushMiddlewareToGroup('web', SeoTraffic::class);

        $this->loadRoutesFrom(
            $this->app->make('path.haxibiao-content') . '/router.php'
        );

        //cms站点
        $this->app->singleton('cms_site', function ($app) {

            $modelStr = '\Haxibiao\Content\Site';
            if (class_exists('\App\Site')) {
                // \App\Site 是 \Haxibiao\Cms\Site 的子类
                $modelStr = '\App\Site';
            }
            if ($site = $modelStr::whereDomain(get_domain())->first()) {
                return $site;
            }
            //默认返回最后一个站点
            return $modelStr::latest('id')->first();
        });

        if (config('cms.multi_domains')) {
            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);
                // 每天定时归档seo流量
                $schedule->command('archive:traffic')->dailyAt('1:00');

                // 自动更新站群首页资源
                $schedule->command('cms:update')->dailyAt('2:00');

                // 生成新的SiteMap
                $schedule->command('sitemap:generate')->dailyAt('3:00');

            });
        }
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
