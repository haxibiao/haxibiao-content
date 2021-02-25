<?php
namespace Haxibiao\Content\Traits;

use Haxibiao\Content\Site;
use Haxibiao\Content\Siteable;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait WithCms
{
    public function sites(): MorphToMany
    {
        return $this->morphToMany(Site::class, 'siteable')
            ->withTimestamps();
    }

    public function siteable()
    {
        return $this->morphMany(Siteable::class, 'siteable');
    }

    /**
     * 分配内容到站点
     */
    public function assignToSite($site_id)
    {
        $this->sites()->syncWithoutDetaching([$site_id]);
    }

    //attrs
    public function getBaiduPushedAtAttribute()
    {
        if ($this->pivot) {
            return $this->pivot->baidu_pushed_at;
        }
        return null;
    }

    // content里已确保提交给百度的都有url属性
}
