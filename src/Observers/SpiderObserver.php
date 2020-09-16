<?php

namespace Haxibiao\Content\Observers;

use App\Post;
use Haxibiao\Media\Spider;

class SpiderObserver
{
    /**
     * Handle the spider "created" event.
     *
     * @param  \App\Spider  $spider
     * @return void
     */
    public function created(Spider $spider)
    {
        //创建爬虫的时候，自动发布一个动态
        Post::saveSpiderVideoPost($spider);
    }

    /**
     * Handle the spider "updated" event.
     *
     * @param  \App\Spider  $spider
     * @return void
     */
    public function updated(Spider $spider)
    {
        // 这里考虑用event来触发 && 方便不同产品的奖励机制
        if ($spider->status == Spider::PROCESSED_STATUS) {
            Post::publishSpiderVideoPost($spider);
        }
    }

    /**
     * Handle the spider "deleted" event.
     *
     * @param  \App\Spider  $spider
     * @return void
     */
    public function deleted(Spider $spider)
    {
        //
    }

    /**
     * Handle the spider "restored" event.
     *
     * @param  \App\Spider  $spider
     * @return void
     */
    public function restored(Spider $spider)
    {
        //
    }

    /**
     * Handle the spider "force deleted" event.
     *
     * @param  \App\Spider  $spider
     * @return void
     */
    public function forceDeleted(Spider $spider)
    {
        //
    }
}
