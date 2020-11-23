<?php

namespace Haxibiao\Content\Traits;

use App\User;
use Illuminate\Support\Str;

trait IssueAttrs
{

    public function getImageUrlsAttribute()
    {
        $urls = $this->images()->pluck('path')->map(function ($url) {
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
        $image_url = $this->image_urls->first();
        if (isset($image_url)) {
            if (Str::contains($image_url, 'http')) {
                return $image_url;
            }
            return \Storage::cloud()->url($this->image_url);
        }
        // 避免前端取不到数据
        return \Storage::cloud()->url(User::AVATAR_DEFAULT);

    }

    public function getImageAttribute()
    {
        return $this->images->first();
    }

}
