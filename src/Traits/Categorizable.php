<?php

namespace Haxibiao\Content\Traits;

use App\Category;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * 分类能力
 */
trait Categorizable
{

    /**
     * 加入过的专题
     */
    public function belongsToCategories()
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

    //用户的图解专题
    public function diagramCategories()
    {
        return $this->hasMany(Category::class, 'user_id', 'id')
            ->where('type', 'diagrams')
            ->where('status', Category::STATUS_PUBLISH);
    }

    public function getCountCategoriesAttribute()
    {
        return $this->categories()->count() ?? 0;
    }

    /**
     *  == 用于内容归类
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function allCategories()
    {
        return $this->morphToMany(Category::class, 'categorizable');
        // ->withPivot(['id', 'submit'])
        // ->withTimestamps();
    }

    //FIXME: 冗余categories()
    public function hasCategories()
    {
        return $this->morphToMany(Category::class, 'categorizable');
    }

    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categorizable')
            ->withPivot(['id', 'submit'])
            ->withTimestamps();
    }

    /**
     * 加入专题(可重)
     */
    public function addCategories($cate_ids = [])
    {
        $cate_data = [];
        foreach ($cate_ids as $cate_id) {
            $cate_data = [
                $cate_id => [
                    'submit' => '已收录',
                ],
            ];
        }
        $this->categories()->sync($cate_data, false);
        return $this;
    }

    /**
     * 更新专题(排重)
     */
    public function updateCategories($cate_ids = [])
    {
        $cate_data = [];
        foreach ($cate_ids as $cate_id) {
            $cate_data = [
                $cate_id => [
                    'submit' => '已收录',
                ],
            ];
        }
        $this->categories()->sync($cate_data);
        return $this;
    }

    /**
     * 脱离专题
     */
    public function removeCategories($cate_ids)
    {
        $this->categories()->detach($cate_ids);
        return $this;
    }

    public function hasCategory($categories)
    {
        if (is_string($categories)) {
            return $this->categories->contains('name', $categories);
        }

        if ($categories instanceof Category) {
            return $this->categories->contains('id', $categories->id);
        }

        if (is_array($categories)) {
            foreach ($categories as $category) {
                if ($this->hasCategory($category)) {
                    return true;
                }
            }

            return false;
        }

        return $categories->intersect($this->categories)->isNotEmpty();
    }
}
