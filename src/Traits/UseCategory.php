<?php

namespace Haxibiao\Content\Traits;

use App\Category;

/**
 * 用户使用分类/专题系统(创建，加入，管理...)
 */
trait UseCategory
{

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function adminCategories()
    {
        return $this->belongsToMany(Category::class)->where('type', 'article')->wherePivot('is_admin', 1);
    }

    public function requestCategories()
    {
        return $this->belongsToMany(Category::class)->wherePivot('approved', 0);
    }

    public function joinCategories()
    {
        return $this->belongsToMany(Category::class)->wherePivot('approved', 1);
    }

    public function hasManyCategories()
    {
        return $this->hasMany(Category::class, 'user_id', 'id')->where('type', 'article');
    }

    public function newReuqestCategories()
    {
        return $this->adminCategories()->orderBy('new_requests', 'desc')->orderBy('updated_at', 'desc');
    }

    public function hasCategories()
    {
        return $this->hasMany(Category::class, 'user_id', 'id')->where('type', 'diagrams')->where('status', '1');
    }

    public function getCountCategoriesAttribute()
    {
        return $this->categories()->count() ?? 0;
    }
}
