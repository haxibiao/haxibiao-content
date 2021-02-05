<?php

namespace Haxibiao\Content;

use App\Visit;
use Haxibiao\Breeze\Model;
use Haxibiao\Breeze\Traits\HasFactory;
use Haxibiao\Content\Traits\CollectionResolvers;
use Haxibiao\Helpers\Traits\Searchable;
use Haxibiao\Sns\Traits\Followable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class Collection extends Model
{
    use CollectionResolvers;
    use Searchable;
    use SoftDeletes;
    use Followable;
    use HasFactory;

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
            'collections.name'        => 1,
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
        $logo        = $this->getRawOriginal('logo');
        if (!$logo) {
            return $defaultLogo;
        }

        $isValidateUrl = filter_var($logo, FILTER_VALIDATE_URL);
        if ($isValidateUrl) {
            return $logo;
        }
        return cdnurl($logo);
    }

    public function getImageAttribute()
    {
        if (starts_with($this->logo, 'http')) {
            return $this->logo;
        }
        $localFileExist = !is_prod() && Storage::disk('public')->exists($this->logo);
        if ($localFileExist) {
            return env('LOCAL_APP_URL') . '/storage/' . $this->logo;
        }

        return cdnurl($this->logo);
    }

    public function getCountPlaysAttribute()
    {
        return numberToReadable(data_get($this, 'count_views', 0));
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
        return $this->posts()->count();
    }

    /**
     * 合集收录内容
     */
    public function collect($collectableIds, $collectableType)
    {

        $index = 1;

        $modelStr = Relation::getMorphedModel($collectableType);
        $modelIds = $modelStr::whereIn('id', $collectableIds)->get()->pluck('id')->toArray();
        $modelIds = array_flip($modelIds);

        $syncData = [];
        foreach ($collectableIds as $collectableId) {
            // 跳过脏数据
            if (!array_key_exists($collectableId, $modelIds)) {
                continue;
            }
            $syncData[$collectableId] = [
                'sort_rank'       => $index,
                'collection_name' => $this->name,
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
        $modelIds = array_flip($modelIds);

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
                'sort_rank'       => $maxSortRank,
                'collection_name' => $this->name,
            ];
        }
        $this->collectable($modelStr)
            ->sync($syncData, false);

        $this->updateCountPosts();

        return $this;
    }

    public static function getTopCover()
    {
        // 测试环境跳过
        if(config('app.env') == 'testing'){
            return;
        }
        return cdnurl(self::TOP_COVER);
        // $update_time = Storage::lastModified(self::TOP_COVER);
        // // $update_time = Storage::cloud()->lastModified(self::TOP_COVER);
        // $interval    = ceil((time() - $update_time));

        // //如果今天更新过，则拷贝一份新的更新名字
        // $newCover = 'storage/collection/new_top_cover.png';
        // if ($interval <= 1000) {
        //     Storage::cloud()->copy(self::TOP_COVER, $newCover);
        //     return cdnurl($newCover);
        // }
        // //如果在规定时间内没有访问更新后的图片，更新缓存
        // if (mt_rand(1, 100) > 50) {
        //     return cdnurl($newCover);
        // } else {
        //     return cdnurl(self::TOP_COVER);
        // }
    }

    public static function setTopCover($file)
    {
        if ($file) {
            //UploadedFile
            $cover       = self::TOP_COVER;
            $imageStream = file_get_contents($file->getRealPath());
            return Storage::cloud()->put($cover, $imageStream);
        }
        return cdnurl(self::TOP_COVER);
    }

    /**
     * 更新集数
     */
    public function updateCountPosts()
    {
        if (!Schema::hasColumn('collections', 'count_posts')) {
            return;
        }
        $this->count_posts = $this->posts()->count();
        $this->save();
    }

    /**
     * 上传合集封面
     */
    public function saveDownloadImage($file)
    {
        if ($file) {
            $cover   = '/collect' . $this->id . '_' . time() . '.png';
            $cosDisk = Storage::cloud();
            $cosDisk->put($cover, \file_get_contents($file->path()));

            return cdnurl($cover);
        }
    }
}
