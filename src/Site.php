<?php

namespace Haxibiao\Content;

use Haxibiao\Breeze\Traits\HasFactory;
use Haxibiao\Content\Traits\Stickable;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    use Stickable;
    use HasFactory;

    public $casts = [
        'json' => 'array', //索引趋势数据
        'data' => 'array', //今日百度提交配额
    ];

    protected $guarded = [];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeBySiteIds($query, $siteIds)
    {
        return $query->whereIn('id', $siteIds);
    }

    //专题
    public function categories($userAppModel = false)
    {
		return $this->siteable(\App\Category::class);
    }

    //文章
    public function articles($userAppModel = false)
    {
        //允许App层重写覆盖属性
		return $this->siteable(\App\Article::class);
    }

    //动态
    public function posts($userAppModel = false)
    {
        //允许App层重写覆盖属性
        return $this->siteable(\App\Post::class);
    }

    //电影
    public function movies($userAppModel = false)
    {
		return $this->siteable(\App\Movie::class);
    }

    public function siteable($related)
    {
        return $this->morphedByMany($related, 'siteable')->withPivot([
            'baidu_pushed_at',
        ]);
    }

    public function related()
    {
        return $this->hasMany(Siteable::class);
    }

    //attrs
    public function getBaiduSuccessAttribute()
    {
        return $this->data['baidu_success'] ?? 0;
    }

    public function getBaiduRemainAttribute()
    {
        return $this->data['baidu_remain'] ?? '未知';
    }
}
