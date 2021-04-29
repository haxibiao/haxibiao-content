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
            $post->review_day = Post::makeNewReviewDay();
            $post->saveQuietly();
        }
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
