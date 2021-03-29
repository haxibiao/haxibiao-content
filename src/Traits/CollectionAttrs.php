<?php

namespace Haxibiao\Content\Traits;

use Illuminate\Support\Facades\Storage;

trait CollectionAttrs
{

    public function getLogoAttribute()
    {
        //默认封面
        $defaultLogo = config('haxibiao-content.collection_default_logo', 'https://haxibiao-1251052432.cos.ap-guangzhou.myqcloud.com/images/collection.png');
        $logo        = $this->getRawOriginal('logo');
        if (!$logo) {
            return $defaultLogo;
        }

        //当合集封面是 full url ..
        $isUrl = filter_var($logo, FILTER_VALIDATE_URL);
        if ($isUrl) {
            return $logo;
        }

        //自定义上传的logoPath
        return cdnurl($logo);
    }

    public function getImageAttribute()
    {
        if (starts_with($this->logo, 'http')) {
            return $this->logo;
        }
        $localFileExist = !is_prod() && Storage::disk('public')->exists($this->logo);
        if ($localFileExist) {
            return env('LOCAL_APP_URL') . '/storage/' . $this->logo;
        }

        return cdnurl($this->logo);
    }

    public function getCountPlaysAttribute()
    {
        return numberToReadable(data_get($this, 'count_views', 0));
    }

    public function getUpdatedToEpisodeAttribute()
    {
        return $this->posts()->count();
    }
}