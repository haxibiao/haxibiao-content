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
        if ($user = checkUser()) {
            return $user->isLiked('posts', $this->id);
        }
        return false;
    }
}
