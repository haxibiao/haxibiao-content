<?php

namespace Haxibiao\Content\Traits;
use App\Visit;
use Illuminate\Support\Str;
use App\User;

trait SolutionAttrs
{

    public function getImageUrlsAttribute()
    {
        $urls= $this->images()->pluck('path')->map(function ($url) {
            if (isset($url)) {
                if (Str::contains($url, 'http')) {
                    return $url;
                }
                return \Storage::cloud()->url($url);
            }
            return \Storage::cloud()->url(User::AVATAR_DEFAULT);

        });
        return $urls;
    }
    public function getImageCoverAttribute()
    {
        if (isset($this->image_url)) {
            if (Str::contains($this->image_url, 'http')) {
                return $this->image_url;
            }
            return \Storage::cloud()->url($this->image_url);
        }
        // 避免前端取不到数据
        return \Storage::cloud()->url(User::AVATAR_DEFAULT);

    }

    public function getLikedAttribute()
    {
        if ($user = getUser(false)) {
            return $like = $user->likedSolutions()->where('liked_id', $this->id)->count() > 0;
        }
        return false;
    }
    public function getCountVisitsAttribute()
    {
        return  Visit::where('visited_id',$this->id)->where('visited_type','solutions')->count();
    }
}
