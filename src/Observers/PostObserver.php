<?php

namespace Haxibiao\Content\Observers;

use Haxibiao\Content\Post;

class PostObserver
{
/**
 * Handle the Post "created" event.
 *
 * @param  \App\Post  $post
 * @return void
 */
    public function created(Post $post)
    {
        //自动更新快速推荐排序游标
        if (blank($post->review_id)) {
            $post->review_id  = Post::makeNewReviewId();
            $post->review_day = Post::genReviewDay();
            $post->saveQuietly();

            //FIXME: 超过100个今日新动态或者已经有1个小时未归档了，自动发布归档并刷新推荐排序. 建议增加 review_at字段
            // $canAutoReview = Post::where('review_day', 0)
            //     ->where('created_at', '<=', now()->subHour())->exists()
            // || Post::where('review_day', 0)->count() >= 100;

            // if ($canAutoReview) {
            //     dispatch(new ArchiveTodayPosts);
            // }
        }

        //createPost 重构出来的冗余操作
        // 记录用户操作
        // Action::createAction('posts', $post->id, $post->user->id);

        //添加定位信息
        // if (in_array(config('app.name'), ['dongwaimao', 'jinlinle']) && !empty(data_get($inputs, 'location'))) {
        //     \App\Location::storeLocation(data_get($inputs, 'location'), 'posts', $post->id);
        // }

        //FIXME: 触发更新事件-扣除精力点？

        app_track_event('发布', '发布Post动态');

    }

    /**
     * Handle the Post "updated" event.
     *
     * @param  \App\Post  $post
     * @return void
     */
    public function updated(Post $post)
    {

    }

    /**
     * Handle the Post "deleted" event.
     *
     * @param  \App\Post  $post
     * @return void
     */
    public function deleted(Post $post)
    {

    }
}
