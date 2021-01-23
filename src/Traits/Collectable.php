<?php

namespace Haxibiao\Content\Traits;

use Haxibiao\Content\Collection;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait Collectable
{

    public static function bootCollectable()
    {
        static::deleting(function ($model) {
            $collectionIds = $model->collections()->get()->pluck('id');
            $model->uncollectivize($collectionIds);
        });
    }

    public function collections(): MorphToMany
    {
        return $this->morphToMany(Collection::class, 'collectable')
            ->orderBy('type')
            ->withPivot(['id', 'collection_name', 'sort_rank'])
            ->withTimestamps();
    }

    public function collectable()
    {
        return $this->morphMany(\Haxibiao\Content\Collectable::class, 'collectable');
    }

    /**
     * 以前的文章系统中有一个主文集的概念
     * @return mixed
     */
    public function collection()
    {
        return $this->belongsTo(Collection::class, 'collection_id');
    }

    /**
     * 内容增加到几个合集
     */
    public function collectivize($collection_ids)
    {
        $syncData    = [];
        $collections = Collection::byCollectionIds($collection_ids)->get();
        $index       = 1;
        foreach ($collections as $collection) {
            $syncData[$collection->id] = [
                'sort_rank'       => $index,
                'collection_name' => $collection->name,
            ];
            $collection->updateCountPosts();
            $index++;
        }
        $this->collections()->sync($syncData, false);

        return $this;
    }

    /**
     * 内容强制刷新为当前几个合集，丢掉以前的合集关联
     */
    public function recollectivize($collection_ids = [])
    {
        $syncData    = [];
        $collections = Collection::byCollectionIds($collection_ids)->get();
        $index       = 1;
        foreach ($collections as $collection) {
            $syncData[$collection->id] = [
                'sort_rank'       => $index,
                'collection_name' => $collection->name,
            ];
            $collection->updateCountPosts();
            $index++;
        }
        $this->collections()->sync($syncData);

        return $this;
    }

    public function uncollectivize($collections)
    {
        $this->collections()->detach($collections);
        $collections = Collection::byCollectionIds($collections)->get();
        foreach ($collections as $collection) {
            $collection->updateCountPosts();
        }

        return $this;
    }

    public function getCurrentEpisodeAttribute()
    {
        $collection = $this->collections()
            ->latest()->first();
        if (!$collection) {
            return null;
        }
        $results = $collection->posts()->orderBy('collectables.sort_rank', 'asc')->get();
        $index   = 1;
        foreach ($results as $result) {
            if (data_get($result, 'pivot.collectable_id') == $this->id) {
                return $index;
            }
            $index++;
        }
        return null;
    }
}
