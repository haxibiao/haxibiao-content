<?php

namespace Haxibiao\Content\Jobs;

use Haxibiao\Content\Traits\FastRecommendStrategy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * 处理今日新动态的随机推荐排序
 */
class ArchiveTodayPosts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        FastRecommendStrategy::archiveTodayPosts();
    }
}
