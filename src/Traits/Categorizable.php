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
            ->where('status', '1');
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

    public function categorize($categories)
    {
        $this->categories()->sync($categories, false);

        return $this;
    }

    public function recategorize($categories = [])
    {
        $this->categories()->sync($categories);

        return $this;
    }

    public function uncategorize($categories)
    {
        $this->categories()->detach($categories);

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
