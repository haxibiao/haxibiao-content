<?php

namespace Haxibiao\Content\Traits;

use Haxibiao\Content\Location;

trait PostAttrs
{
    public function getUrlAttribute()
    {
        $path = '/%s/%d';
        if ($this->type == 'video') {
            return sprintf($path, $this->type, $this->video_id);
        }
        $path = sprintf($path, 'post', $this->id);
        return url($path);
    }

    public function getTimeAgeAttribute()
    {
        return time_ago($this->created_at);
    }

    public function getCoverUrlAttribute()
    {
        return $this->getCoverAttribute();
    }

    public function getCoverAttribute()
    {
        return $this->video ? $this->video->cover_url : null;
    }

    //use Likeable
    // public function getLikedAttribute()
    // {
    //     //检查下是否预加载了,预加载后,则无需重复查询
    //     $isPredloaded = isset($this->attributes['liked']);
    //     $liked        = $isPredloaded ? $this->attributes['liked'] : false;
    //     if (!$isPredloaded && $user = checkUser()) {
    //         $liked = $user->isLiked('posts', $this->id);
    //     }

    //     return $liked;
    // }

    public function getDistanceAttribute()
    {
        if (checkUser() && !empty(getUser(false)->location) && !empty($this->location)) {
            $user       = getUser();
            $longitude1 = $user->location->longitude;
            $latitude1  = $user->location->latitude;
            $longitude2 = $this->location->longitude;
            $latitude2  = $this->location->latitude;
            if ($longitude1 && $latitude1 && $longitude2 && $latitude2) {
                $distance = Location::getDistance($longitude1, $latitude1, $longitude2, $latitude2, 1);
                return numberToReadable($distance) . 'm';
            }
        } else {
            return null;
        }

    }
}
