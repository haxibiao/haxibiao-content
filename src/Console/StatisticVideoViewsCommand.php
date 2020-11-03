<?php
namespace Haxibiao\Content\Console;

use App\Collection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class StatisticVideoViewsCommand  extends Command
{
    protected $signature = 'haxibiao:statistic:video_viewers';

    protected $description = '以天为单位更新视频总播放量(可重复执行)';

    public function handle()
    {
        /**
         * 更新合集中的视频播放量
         */
        if (Schema::hasColumn('collections', 'count_views')){
            $this->info('statistic collections ...');
            Collection::chunk(100, function ($collections) {
                foreach ($collections as $collection) {

                    $countViews = 0;
                    $collection->posts()->each(function ($post) use (&$countViews) {
                        $countViews += data_get($post, 'video.json.count_views', 0);
                    });

                    $collection->count_views = $countViews;

                    $dispatcher = $collection->getEventDispatcher();
                    $collection->unsetEventDispatcher();
                    $collection->timestamps = false;
                    $collection->setEventDispatcher($dispatcher);
                    $collection->save();
                }
            });
            $this->info('statistic collections success');
        }
    }
}