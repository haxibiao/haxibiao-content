<?php

namespace Haxibiao\Content\Traits;

use App\Collection;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait Collectable
{
    /**
     * 监听内容删除事件自动移除合集关系
     */
    public static function bootCollectable()
    {
        static::deleting(function ($model) {
            $collectionIds = $model->collections()->get()->pluck('id');
            $model->dropCollections($collectionIds);
        });
    }

    /**
     * 用户的合集
     */
    public function hasCollections()
    {
        return $this->hasMany(Collection::class);
    }

    /**
     * 内容的合集
     */
    public function collections()
    {
        return $this->morphToMany(Collection::class, 'collectable')
            ->orderBy('type')
            ->withPivot(['id', 'collection_name', 'sort_rank'])
            ->withTimestamps();
    }

    public function collectable()
    {
        return $this->morphMany(Collectable::class, 'collectable');
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
    public function addCollections($collection_ids)
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
    public function updateCollections($collection_ids = [])
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

    public function dropCollections($collections)
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
        return $this->pivot->sort_rank;
    }
}
