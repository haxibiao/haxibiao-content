<?php

namespace Haxibiao\Content;

use App\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;

class Tag extends Model
{
    protected $table = 'tags';
    public $guarded  = [];

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

    /**
     * 格式化Name的访问器
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = trim($value);
    }

    /**
     * 增加标签被引用的次数
     */
    public function incrementCount($count)
    {
        if ($count <= 0) {return;}
        $this->count = $this->count + $count;
        $this->save();
    }

    /**
     * 减少标签被引用的次数
     */
    public function decrementCount($count)
    {
        if ($count <= 0) {return;}

        $this->count = $this->count - $count;
        if ($this->count < 0) {
            $this->count = 0;
        }
        $this->save();
    }

    public function resovelAddTags($rootValue, $args, $context, $resolveInfo)
    {

        $name         = data_get($args, 'name');
        $tagIds       = data_get($args, 'tag_ids', []);
        $taggableId   = data_get($args, 'taggable_id');
        $taggableType = data_get($args, 'taggable_type');

        $modelString = Relation::getMorphedModel($taggableType);
        if (!class_exists($modelString)) {
            return false;
        }
        $model = $modelString::findOrFail($taggableId);
        if ($tagIds) {
            $model->tagByIds($tagIds);
        }
        if ($name) {
            $model->tagByNames($name);
        }
        return true;
    }

    public static function resolveTags($root, $args, $context, $info)
    {
        $qb = static::query();

        //返回首页置顶的4个标签
        if ($args['filter'] == 'HOT') {
            return $qb->orderByDesc('count')
                ->whereBetWeen('created_at', [now()->subDay(14), now()]);
        }
        return $qb->whereBetWeen('created_at', [now()->subDay(14), now()]);
    }
}
