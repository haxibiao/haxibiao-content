<?php

namespace Haxibiao\Content\Traits;

use App\Collection;

use App\Tag;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait CanCollect
{

    public static function bootCanCollect()
    {
        static::deleting(function($model) {
            $collectionIds = $model->collections()->get()->pluck('id');
            $model->uncollectivize($collectionIds);
        });
    }

    public function collections(): MorphToMany
    {
        return $this->morphToMany(\App\Collection::class, 'collectable')
            ->withPivot(['id', 'collection_name','sort_rank'])
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
        return $this->belongsTo(\App\Collection::class, 'collection_id');
    }

    public function collectivize($collections)
    {
        $syncData       = [];
        $collections    = Collection::byCollectionIds($collections)->get();
        $index = 1;
        foreach ($collections as $collection){
            $syncData[$collection->id] = [
                'sort_rank'          => $index,
                'collection_name'   => $collection->name
            ];
            $collection->updateCountPosts();
            $index++;
        }
        $this->collections()->sync($syncData, false);

        return $this;
    }

    public function recollectivize($collections = [])
    {
        $syncData       = [];
        $collections    = Collection::byCollectionIds($collections)->get();
        $index = 1;
        foreach ($collections as $collection){
            $syncData[$collection->id] = [
                'sort_rank'          => $index,
                'collection_name'   => $collection->name
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
        $collections    = Collection::byCollectionIds($collections)->get();
        foreach ($collections as $collection){
            $collection->updateCountPosts();
        }

        return $this;
    }

    public function getCurrentEpisodeAttribute(){
        $collection = $this->collections()
            ->latest()->first();
        if(!$collection){
            return null;
        }
        $results = $collection->posts()->orderBy('collectables.sort_rank','asc')->get();
        $index = 1;
        foreach ($results as $result){
            if(data_get($result,'pivot.collectable_id') == $this->id){
                return $index;
            }
            $index ++;
        }
        return null;
    }
}
