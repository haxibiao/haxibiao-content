<?php

namespace Haxibiao\Content\Observers;

use App\Post;
use App\Spider;
use Haxibiao\Media\Video;

class VideoObserver
{
    /**
     * Handle the video "created" event.
     *
     * @param  \App\Video  $video
     * @return void
     */
    public function created(Video $video)
    {

    }

    /**
     * Handle the video "updated" event.
     *
     * @param  \App\Video  $video
     * @return void
     */
    public function updated(Video $video)
    {
        if ($video->cover) {
            if ($post = Post::where('video_id', $video->id)->first()) {
                Post::publishPost($post);
            } else {
                //支持另一种情况
                $spider = Spider::where('spider_type', 'videos')->where('spider_id', $video->id)->first();
                if ($spider) {
                    if ($post = Post::where('spider_id', $spider->id)->first()) {
                        $post->status      = Post::PUBLISH_STATUS; //发布成功动态
                        $post->description = $spider->data['titcle'] ?? '';
                        $post->content     = $spider->data['titcle'] ?? '';
                        $post->video_id    = $video->id;
                        $post->save();
                    }
                }
            }
        }
    }

    /**
     * Handle the video "deleted" event.
     *
     * @param  \App\Video  $video
     * @return void
     */
    public function deleted(Video $video)
    {
        //
    }

    /**
     * Handle the video "restored" event.
     *
     * @param  \App\Video  $video
     * @return void
     */
    public function restored(Video $video)
    {
        //
    }

    /**
     * Handle the video "force deleted" event.
     *
     * @param  \App\Video  $video
     * @return void
     */
    public function forceDeleted(Video $video)
    {
        //
    }
}
