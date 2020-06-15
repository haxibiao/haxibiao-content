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
        //时间差，视频发布时间与今天的时间差
        (string)$timeDifference = Carbon::parse($this->created_at)->diffInDays(today());

        if (0 == $timeDifference) {
            return "今天";
        }
        return $timeDifference . '天前';
    }
}
