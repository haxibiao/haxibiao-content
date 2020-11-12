<?php

namespace Haxibiao\Content;

use App\Visit;
use Haxibiao\Base\Traits\ModelHelpers;
use Haxibiao\Content\Traits\BaseModel;
use Illuminate\Support\Facades\Schema;
use Haxibiao\Helpers\Traits\Searchable;
use Illuminate\Database\Eloquent\Model;
use Haxibiao\Helpers\Traits\PivotEventTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Haxibiao\Content\Traits\CollectionResolvers;
use Illuminate\Database\Eloquent\Relations\Relation;

class Collection extends Model
{
    use CollectionResolvers;
    use Searchable;
    use SoftDeletes;
    use BaseModel;


    protected $table = 'collections';

    /**
     * 上架状态
     */
    const STATUS_ONLINE = 1;
    /* 推荐集合 */
    const RECOMMEND_COLLECTION = 2;
    /* 置顶集合 */
    const TOP_COLLECTION = 1;
    //置顶合集图片
    const TOP_COVER = 'storage/collection/top_cover.png';

    protected $searchable = [
        'columns' => [
            'collections.name' => 1,
            'collections.description' => 1,
        ],
    ];

    protected $casts = [
        'json' => 'object',
    ];

    protected $guarded = ['api_token'];

    public static function boot()
    {
        parent::boot();

        //删除时触发
        self::deleted(function ($model) {
            // 移除所有的中间关系
            $model->collectables()->delete();
        });
    }

    public function visits()
    {
        return $this->morphMany(Visit::class, 'visited');
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
        $defaultLogo = config('haxibiao-content.collection_default_logo', 'https://haxibiao-1251052432.cos.ap-guangzhou.myqcloud.com/images/collection.png');
        $logo = $this->getRawOriginal('logo');
        if (!$logo) {
            return $defaultLogo;
        }

        $isValidateUrl = filter_var($logo, FILTER_VALIDATE_URL);
        if ($isValidateUrl) {
            return $logo;
        }
        return \Storage::cloud()->url($logo);
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

        return \Storage::cloud()->url($this->logo);
    }

    public function getCountPlaysAttribute()
    {
        return numberToReadable(data_get($this,'count_views',0));
    }

    public function scopeByCollectionIds($query, $collectionIds)
    {
        return $query->whereIn('id', $collectionIds);
    }
    public function scopeTop($query)
    {
        return $query->where('sort_rank', self::TOP_COLLECTION);
    }


    public function getUpdatedToEpisodeAttribute()
    {
        return  $this->count_posts;
    }

    public function collect($collectableIds, $collectableType)
    {

        $index = 1;

        $modelStr = Relation::getMorphedModel($collectableType);
        $modelIds = $modelStr::whereIn('id', $collectableIds)->get()->pluck('id')->toArray();
        $modelIds  = array_flip($modelIds);

        $syncData = [];
        foreach ($collectableIds as $collectableId) {
            // 跳过脏数据
            if (!array_key_exists($collectableId, $modelIds)) {
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

        $this->updateCountPosts();

        return $this;
    }

    public function uncollect($collectableIds, $collectableType)
    {

        $modelStr = Relation::getMorphedModel($collectableType);
        $this->collectable($modelStr)
            ->detach($collectableIds);

        $this->updateCountPosts();

        return $this;
    }

    public function recollect($collectableIds, $collectableType)
    {

        $modelStr = Relation::getMorphedModel($collectableType);
        $modelIds = $modelStr::whereIn('id', $collectableIds)->get()->pluck('id')->toArray();
        $modelIds  = array_flip($modelIds);

        $maxSortRank = $this->collectable($modelStr)
            ->get()
            ->max('pivot.sort_rank') ?: 0;

        $syncData = [];
        foreach ($collectableIds as $collectableId) {
            $maxSortRank++;

            // 跳过脏数据
            if (!array_key_exists($collectableId, $modelIds)) {
                continue;
            }
            $syncData[$collectableId] = [
                'sort_rank'          => $maxSortRank,
                'collection_name'    => $this->name
            ];
        }
        $this->collectable($modelStr)
            ->sync($syncData, false);

        $this->updateCountPosts();

        return $this;
    }

    public static function getTopCover()
    {
        return \Storage::cloud()->url(self::TOP_COVER);
    }

    public static function setTopCover($file)
    {
        if ($file) {
            //UploadedFile
            $cover = self::TOP_COVER;
            $imageStream = file_get_contents($file->getRealPath());
            return \Storage::cloud()->put($cover, $imageStream);
        }
        return \Storage::cloud()->url(self::TOP_COVER);
    }

    /**
     * 更新集数
     */
    public function updateCountPosts(){
        if (!Schema::hasColumn('collections', 'count_posts')){
            return;
        }
        $this->count_posts = $this->posts()->count();
        $this->save();
    }

        //只保存数据，不更新时间
        public function saveDataOnly()
        {
            //获取model里面的事件
            $dispatcher = self::getEventDispatcher();
    
            //不触发事件
            self::unsetEventDispatcher();
            $this->timestamps = false;
            $this->save();
    
            //启用事件
            self::setEventDispatcher($dispatcher);
        }
}
