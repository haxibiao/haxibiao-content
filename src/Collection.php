<?php

namespace Haxibiao\Content;

use Haxibiao\Content\Traits\CollectionResolvers;
use Haxibiao\Helpers\Traits\Searchable;
use Illuminate\Database\Eloquent\Model;
use Haxibiao\Sns\Traits\CanBeFollow;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;

class Collection extends Model
{
    use CollectionResolvers;
    use CanBeFollow;
    use Searchable;
    use SoftDeletes;

    protected $table = 'collections';

    /**
     * 上架状态
     */
    const STATUS_ONLINE = 1;

    protected $searchable = [
        'columns' => [
            'collections.name' => 1,
            'collections.description' => 1,
        ],
    ];

    protected $casts = [
        'json' => 'object',
    ];

    protected $guarded = [];

    public static function boot()
    {
        parent::boot();

        //删除时触发
        self::deleted(function ($model) {
            // 移除所有的中间关系
            $model->collectables()->delete();
        });
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class);
    }

    public function collectable($related)
    {
        return $this->morphedByMany($related, 'collectable')
            ->withTimestamps()
            ->withPivot(['sort_rank']);
    }

    public function collectables()
    {
        return $this->hasMany(Collectable::class);
    }

    public function posts()
    {
        return $this->collectable(\App\Post::class)->withTimestamps()
            ->withPivot(['sort_rank']);
    }

    public function articles()
    {
        return $this->collectable(\App\Article::class)->withTimestamps();
    }

    public function hasManyArticles()
    {
        return $this->articles()->where('status', '>=', '0');
    }

    public function publishedArticles()
    {
        return $this->articles()->where('status', '>=', '0');
    }

    public function getLogoAttribute()
    {
        $defaultLogo = config('haxibiao-content.collection_default_logo','https://haxibiao-1251052432.cos.ap-guangzhou.myqcloud.com/images/collection.png');
        $logo = $this->getRawOriginal('logo');
        if(!$logo){
           return $defaultLogo;
        }

        $isValidateUrl = filter_var($logo, FILTER_VALIDATE_URL);
        if($isValidateUrl){
            return $logo;
        }

        return \Storage::disk('cosv5')->url($logo);
    }

    public function getImageAttribute()
    {
        if (starts_with($this->logo, 'http')) {
            return $this->logo;
        }
        $localFileExist = !is_prod() && \Storage::disk('public')->exists($this->logo);
        if ($localFileExist) {
            return env('LOCAL_APP_URL') . '/storage/' . $this->logo;
        }
        return \Storage::disk('cosv5')->url($this->logo);
    }

    public function scopeByCollectionIds($query, $collectionIds)
    {
        return $query->whereIn('id', $collectionIds);
    }

    public function getCountPostsAttribute(){
        return $this->posts()->count();
    }


    public function getUpdatedToEpisodeAttribute(){
        return $this->posts()->count();
    }

    public function collect($collectableIds,$collectableType){

        $index = 1;

        $modelStr = Relation::getMorphedModel($collectableType);
        $modelIds = $modelStr::whereIn('id',$collectableIds)->get()->pluck('id')->toArray();
        $modelIds  = array_flip($modelIds);

        $syncData = [];
        foreach ($collectableIds as $collectableId){
            // 跳过脏数据
            if(!array_key_exists($collectableId,$modelIds )){
                continue;
            }
            $syncData[$collectableId] = [
                'sort_rank'          => $index,
                'collection_name'    => $this->name
            ];
            $index++;
        }
        $this->collectable($modelStr)
            ->sync($syncData);

        return $this;
    }

    public function uncollect($collectableIds,$collectableType){

        $modelStr = Relation::getMorphedModel($collectableType);
        $this->collectable($modelStr)
            ->detach($collectableIds);

        return $this;
    }

    public function recollect($collectableIds,$collectableType){

        $modelStr = Relation::getMorphedModel($collectableType);
        $modelIds = $modelStr::whereIn('id',$collectableIds)->get()->pluck('id')->toArray();
        $modelIds  = array_flip($modelIds);

        $maxSortRank = $this->collectable($modelStr)
            ->get()
            ->max('pivot.sort_rank')?:0;

        $syncData = [];
        foreach ($collectableIds as $collectableId){
            $maxSortRank++;

            // 跳过脏数据
            if(!array_key_exists($collectableId,$modelIds )){
                continue;
            }
            $syncData[$collectableId] = [
                'sort_rank'          => $maxSortRank,
                'collection_name'    => $this->name
            ];
        }
        $this->collectable($modelStr)
            ->sync($syncData,false);

        return $this;
    }
}
