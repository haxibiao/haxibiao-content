<?php

namespace Haxibiao\Content\Traits;

use App\Collection;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait CanCollect
{
    private function collectableModel(): string
    {
        return config('haxibiao-content.models.collection');
    }

    public function collection()
    {
        return $this->belongsTo($this->collectableModel(), 'collection_id');
    }

    public function allCollections()
    {
        return $this->morphToMany($this->collectableModel(), 'collectable')
            ->withPivot(['id', 'collection_name'])
            ->withTimestamps();
    }

    public function hasCollections()
    {
        return $this->morphToMany($this->collectableModel(), 'collectable');
    }

    public function collections(): MorphToMany
    {
        return $this->morphToMany($this->collectableModel(), 'collectable')
            ->withPivot(['id', 'collection_name'])
            ->withTimestamps();
    }

    public function collectable($collections)
    {
        $this->collections()->sync($collections, false);

        return $this;
    }

    public function reCollectable($collections = [])
    {
        $this->collections()->sync($collections);

        return $this;
    }

    public function unCollectable($collections)
    {
        $this->collections()->detach($collections);

        return $this;
    }

    public function hasCollection($collections)
    {
        if (is_string($collections)) {
            return $this->collections()->contains('name', $collections);
        }

        if ($collections instanceof Collection) {
            return $this->collections()->contains('id', $collections->id);
        }

        if (is_array($collections)) {
            foreach ($collections as $collection) {
                if ($this->hasCollection($collection)) {
                    return true;
                }
            }

            return false;
        }

        return $collections->intersect($this->collections)->isNotEmpty();
    }
}
