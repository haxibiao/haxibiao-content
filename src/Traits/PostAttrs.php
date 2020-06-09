<?php

namespace haxibiao\content\Traits;

trait PostAttrs
{
    public function getTimeAgoAttribute()
    {
        return time_ago($this->created_at);
    }

    public function getLikedAttribute()
    {
        if ($user = checkUser()) {
            return $user->isLiked('posts', $this->id);
        }
        return false;
    }
}
