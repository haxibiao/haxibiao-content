<?php

namespace Haxibiao\Content\Traits;

trait TagAttrs
{
    public function getCountPostsAttribute()
    {
        return $this->count;
    }

    public function getCountViewsAttribute()
    {
        $countViews = 0;
        $this->posts()->with('video')->each(function ($post) use (&$countViews) {
            $countViews += data_get($post, 'video.json.count_views', 0);
        });
        return numberToReadable($countViews);
    }
}
