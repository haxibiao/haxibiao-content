<?php

namespace Haxibiao\Content\Traits;

use App\Visit;
use Illuminate\Support\Str;

trait SolutionAttrs
{

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
        if (isset($this->image_url)) {
            if (Str::contains($this->image_url, 'http')) {
                return $this->image_url;
            }
            return cdnurl($this->image_url);
        }
        // 避免前端取不到数据
        return url("/images/cover.png");

    }

    public function getCountVisitsAttribute()
    {
        return $this->attributes['count_visits'] ?? Visit::where('visited_id', $this->id)->where('visited_type', 'solutions')->count();
    }
}
