<?php

namespace haxibiao\content\Traits;

use Carbon\Carbon;

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

    public function getCreatedTimeAttribute()
    {
        (string)$var = Carbon::parse($this->created_at)->diffInDays(today());
        return $var . '天前';
    }
}
