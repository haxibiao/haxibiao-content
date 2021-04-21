<?php

namespace Haxibiao\Content;

use App\Visit;
use Haxibiao\Breeze\Model;
use Haxibiao\Breeze\Traits\HasFactory;
use Haxibiao\Content\Traits\CollectionAttrs;
use Haxibiao\Content\Traits\CollectionResolvers;
use Haxibiao\Helpers\Traits\Searchable;
use Haxibiao\Media\Image;
use Haxibiao\Sns\Traits\Followable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class Collection extends Model
{
    use CollectionAttrs;
    use CollectionResolvers;
    use Searchable;
    use SoftDeletes;
    use Followable;
    use HasFactory;

    protected $table = 'collections';

    /**
     * 状态机
     */
	const STATUS_DELETED = -1; // 删除
    const STATUS_ONLINE  = 1;  // 上架（默认）

    /* 推荐集合 */
    const RECOMMEND_COLLECTION = 2;
    /* 置顶集合 */
    const TOP_COLLECTION = 1;

    const TYPE_OF_ARTICLE = 'articles'; // 文集
    const TYPE_OF_POST    = 'posts'; // 视频合集

    //置顶合集图片
    public static function TOP_COVER()
    {
        //支持各APP不同置顶合集图片(cos公用的时候避免存储覆盖)
        return "storage/collection/top_cover_" . app_name() . ".png";
    }

    protected $searchable = [
        'columns' => [
            'collections.name'        => 1,
            'collections.description' => 1,
        ],
    ];

    protected $casts = [
        'json' => 'object',
    ];

    public function getMorphClass()
    {
        return 'collections';
    }

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

    public function movie()
    {
        return $this->belongsTo(\App\Movie::class);
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
        return $this->hasMany(\App\Article::class);
    }

    public function publishedArticles()
    {
        return $this->hasManyArticles()->where('status', '>', '0');
    }

    public function scopeByCollectionIds($query, $collectionIds)
    {
        return $query->whereIn('id', $collectionIds);
    }

    public function scopeTop($query)
    {
        return $query->where('sort_rank', self::TOP_COLLECTION);
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
        $this->collectable($modelStr)->syncWithoutDetaching($syncData);

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
        if (config('app.env') == 'testing') {
            return;
        }
        $topCover = Image::where('title', Collection::TOP_COVER())
            ->latest('id')
            ->first();
        return isset($topCover) ? cdnurl($topCover->path) : cdnurl(Collection::TOP_COVER());
    }

    public static function setTopCover($file)
    {
        //将置顶合集图片存到images表中，通过title了标识
        if ($file) {
            $image        = Image::saveImage($file);
            $image->title = Collection::TOP_COVER();
            $image->save();
            return cdnurl($image->path);
        }
        return Collection::getTopCover();
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
