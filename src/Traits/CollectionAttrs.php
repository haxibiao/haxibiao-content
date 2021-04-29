<?php

namespace Haxibiao\Content\Traits;

use Illuminate\Support\Facades\Storage;

trait CollectionAttrs
{

    /**
     * 合集封面
     */
    public function getLogoAttribute()
    {
        return $this->logo_url;
    }

    public function getLogoUrlAttribute()
    {
        //默认封面
        $defaultLogo = url('images/collection.png');
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

    /**
     * 合集小图标，暂时未裁剪
     */
    public function getIconUrlAttribute()
    {
        return $this->logo_url;
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
        // return $this->posts()->count();

        //FIXME: 修复collection的 count_posts

        //先简单返回一个集数
        return $this->count_posts;
    }
}
