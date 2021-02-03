<?php

namespace Haxibiao\Content;

use Haxibiao\Breeze\Model;
use Haxibiao\Breeze\Traits\HasFactory;
use Haxibiao\Content\Traits\TagAttrs;
use Haxibiao\Content\Traits\TagRepo;
use Haxibiao\Content\Traits\TagResolvers;
use Haxibiao\Helpers\Traits\Searchable;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tag extends Model
{
    use HasFactory;

    use TagAttrs;
    use TagRepo;
    use TagResolvers;
    use Searchable;

    protected $table = 'tags';
    public $guarded  = [];

    protected $searchable = [
        'columns' => [
            'tags.name' => 1,
        ],
    ];

    // old relationship
    public function creator()
    {
        return $this->belongsTo(\App\User::class);
    }

    public function articles(): MorphToMany
    {
        return $this->taggable('App\Article');
    }

    public function videos(): MorphToMany
    {
        return $this->taggable('App\Video');
    }

    public function posts(): MorphToMany
    {
        return $this->taggable(\App\Post::class);
    }

    public function user()
    {
        return $this->belongsTo('\App\User');
    }

    public function taggable($related): MorphToMany
    {
        return $this->morphedByMany($related, 'taggable')->withTimestamps();
    }

    public function scopeByTagName($query, $tagName)
    {
        $tagName = trim($tagName);
        return $query->where('name', $tagName);
    }

    public function scopeByTagNames($query, $tagNames)
    {
        $formatTagNames = [];
        foreach ($tagNames as $tagName) {
            $formatTagNames[] = trim($tagName);
        }
        return $query->whereIn('name', $formatTagNames);
    }

    public function scopeByTagIds($query, $tagIds)
    {
        return $query->whereIn('id', $tagIds);
    }

    /**
     * 根据标签名获取对应的ID
     */
    public function scopeIdsByNames($query, $tagNames)
    {
        $formatTagNames = [];
        foreach ($tagNames as $tagName) {
            $formatTagNames[] = trim($tagName);
        }
        return $query->whereIn('name', $tagNames)->lists('id');
    }

    public function scopeByUserId($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

}
