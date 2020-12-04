<?php

namespace Haxibiao\Content\Traits;


trait HasCategory
{
    public function categorizableModel(): string
    {
        return config('haxibiao-content.models.category');
    }

    public function categories()
    {
        return $this->belongsToMany($this->categorizableModel());
    }

    public function adminCategories()
    {
        return $this->belongsToMany($this->categorizableModel())->where('type', 'article')->wherePivot('is_admin', 1);
    }

    public function requestCategories()
    {
        return $this->belongsToMany($this->categorizableModel())->wherePivot('approved', 0);
    }

    public function joinCategories()
    {
        return $this->belongsToMany($this->categorizableModel())->wherePivot('approved', 1);
    }

    public function hasManyCategories()
    {
        return $this->hasMany($this->categorizableModel(), 'user_id', 'id')->where('type', 'article');
    }

    public function newReuqestCategories()
    {
        return $this->adminCategories()->orderBy('new_requests', 'desc')->orderBy('updated_at', 'desc');
    }

    public function hasCategories()
    {
        return $this->hasMany($this->categorizableModel(), 'user_id', 'id')->where('type', 'diagrams')->where('status','1');
    }

    public function getCountCategoriesAttribute()
    {
        return $this->categories()->count() ?? 0;
    }
}
