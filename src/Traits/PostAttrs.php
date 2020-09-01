<?php

namespace Haxibiao\Content\Traits;

trait PostAttrs
{
    public function getTimeAgeAttribute()
    {
        return time_ago($this->created_at);
    }

    public function getCoverAttribute()
    {
        return $this->video ? $this->video->cover : "";
    }
    public function getLikedAttribute()
    {
        //检查下是否预加载了,预加载后,则无需重复查询
        $isPredloaded = isset($this->attributes['liked']);
        $liked        = $isPredloaded ? $this->attributes['liked'] : false;
        if (!$isPredloaded && $user = checkUser()) {
            $liked = $user->isLiked('posts', $this->id);
        }

        return $liked;
    }
}
