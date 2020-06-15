<?php

namespace haxibiao\content\Traits;

use Carbon\Carbon;

trait PostAttrs
{
    public function getTimeAgeAttribute()
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
