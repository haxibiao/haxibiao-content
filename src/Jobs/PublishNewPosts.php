<?php

namespace haxibiao\content\Jobs;

use haxibiao\content\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class PublishNewPosts implements ShouldQueue
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
        $reviewDay = Post::makeNewReviewDay();
        Post::where('review_day', 0)->chunk(100, function ($posts) use ($reviewDay) {
            //找到今日最大的review_id
            $maxPostReviewId = Post::where('review_day', $reviewDay)->max('review_id');
            if (is_null($maxPostReviewId)) {
                $maxPostReviewId = Post::makeTodayMinReviewId();
            };
            //批量生成一堆新的review_id
            $reviewIds = Post::makeReviewIds($maxPostReviewId, count($posts));
            foreach ($posts as $index => $post) {
                //统一下架更新review_id,避免用户刷到高位id,导致错过部分视频(当这个小时内批量更新的数量多时，有可能)
                $post->review_id  = $reviewIds[$index];
                $post->review_day = Post::makeNewReviewDay();
                $post->status     = Post::PRIVARY_STATUS; //先不上架，避免被刷到了....
                $post->save();
            }
            //再重新上架回去(一次上架100个，避免刷的指针飘了)
            Post::whereIn('id', $posts->pluck('id'))->update(['status' => Post::PUBLISH_STATUS]);
        });
    }
}
