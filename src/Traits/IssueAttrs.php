<?php

namespace Haxibiao\Content\Traits;

use Illuminate\Support\Str;

trait IssueAttrs
{
    public function getUrlAttribute()
    {
        return '/question/' . $this->id;
    }

    public function getImageUrlsAttribute()
    {
        $urls = $this->images()->pluck('path')->map(function ($url) {
            if (isset($url)) {
                if (Str::contains($url, 'http')) {
                    return $url;
                }
                return cdnurl($url);
            }
            return url("/images/cover.png");

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
            return cdnurl($this->image_url);
        }
        // 避免前端取不到数据
        return url("/images/cover.png");

    }

    public function getImageAttribute()
    {
        return $this->images->first();
    }

}
