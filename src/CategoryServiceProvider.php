<?php

namespace Haxibiao\Category;

use Haxibiao\Category\Console\CategoryReFactoringCommand;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class CategoryServiceProvider extends ServiceProvider
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
        $this->loadMigrationsFrom($this->app->make('path.haxibiao-category.migrations'));

        //TODO 需要加入强制publish选项
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'migrations');

        $this->publishes([
            __DIR__ . '/../config/haxibiao-categorized.php' => config_path('haxibiao-categorized.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../graphql/category' => base_path('graphql/category'),
        ], 'live-graphql');

        //TODO 发布Nova配置文件
        $this->loadRoutesFrom(
            $this->app->make('path.haxibiao-category').'/router.php'
        );
    }

    protected function bindPathsInContainer(){
        foreach ([
                     'path.haxibiao-category'   =>        $root = dirname(__DIR__),
                     'path.haxibiao-category.config'      => $root.'/config',
                     'path.haxibiao-category.database'    => $database = $root.'/database',
                     'path.haxibiao-category.migrations'  => $database.'/migrations',
                     'path.haxibiao-category.seeds'       => $database.'/seeds',
                     'path.haxibiao-category.graphql'     => $database.'/graphql'
                 ] as $abstract => $instance) {
            $this->app->instance($abstract, $instance);
        }
    }

    protected function registerCommands(){
        $this->commands([
            CategoryReFactoringCommand::class,
        ]);
    }

    protected function registerMorphMap(){
        $this->morphMap([
            'categories' => 'Haxibiao\Category\Models\Category',
        ]);
    }

    protected function morphMap(array $map = null, bool $merge = true): array
    {
        return Relation::morphMap($map, $merge);
    }
}
