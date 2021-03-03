<?php

namespace Haxibiao\Content\Traits;

use App\Article;
use App\Category;
use App\Movie;
use App\Post;
use App\Site;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait Stickable
{
    public static function bootStickable()
    {
        // 资源移除时候，自动移除置顶逻辑
        static::deleting(function ($model) {
            if ($model->forceDeleting) {
                foreach ($model->related as $stickable) {
                    $stickable->delete();
                }
            }
        });
    }

    /**
     * 内容已置顶到的站点
     */
    public function stickToSites()
    {
        return $this->morphToMany(Site::class, 'stickable', 'stickables')
            ->withPivot(['name', 'page', 'area'])
            ->withTimestamps();
    }

    public function related(): MorphMany
    {
        return $this->morphMany(\Haxibiao\Content\Stickable::class, 'stickable');
    }

    /**
     * 置顶顶站群下
     */
    public function stickByIds($site_ids = null, $name = null, $page = null, $area = null)
    {
        $sites = Site::bySiteIds($site_ids)->get();
        foreach ($sites as $site) {
            $count = $this->stickToSites()->when($name, function ($q) use ($name) {
                $q->where('stickables.name', $name);
            })->where('site_id', $site->id)->count();

            if ($count >= 1) {
                continue;
            } else {
                $this->stickToSites()->attach([
                    $site->id => [
                        'name' => $name,
                        'page' => $page,
                        'area' => $area,
                    ],
                ]);
            }
        }
        return $this;
    }

    public function unStickByIds($site_ids)
    {
        $this->stickToSites()->detach($site_ids);
        return $this;
    }

    public function scopeByStickablePage($query, $page)
    {
        return $query->where('stickables.page', $page);
    }

    public function scopeByStickableName($query, $name)
    {
        return $query->where('stickables.name', $name);
    }

    public function scopeByStickableArea($query, $area)
    {
        return $query->where('stickables.area', $area);
    }

    //  ====  下面是站点的置顶特性 ===
    public function stickyArticles()
    {
        return $this->stickable(Article::class);
    }

    public function stickyMovies()
    {
        return $this->stickable(Movie::class);
    }

    public function stickyPosts()
    {
        return $this->stickable(Post::class);
    }

    public function stickyCategories()
    {
        return $this->stickable(Category::class);
    }

    public function stickables()
    {
        return $this->hasMany(\Haxibiao\Content\Stickable::class);
    }

    public function stickable($related): MorphToMany
    {
        return $this->morphedByMany($related, 'stickable')
            ->withPivot(['name', 'page', 'area','channel'])
            ->withTimestamps();
    }
}
