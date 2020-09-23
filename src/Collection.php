<?php

namespace Haxibiao\Content;

use Haxibiao\Content\Traits\CollectionResolvers;
use Haxibiao\Helpers\Traits\Searchable;
use Illuminate\Database\Eloquent\Model;
use Haxibiao\Sns\Traits\CanBeFollow;
use Illuminate\Database\Eloquent\SoftDeletes;

class Collection extends Model
{
    use CollectionResolvers;
    use CanBeFollow;
    use Searchable;
    use SoftDeletes;

    protected $searchable = [
        'columns' => [
            'collections.name' => 1,
            'collections.description' => 1,
        ],
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


    //合集中的post
    public function posts()
    {
        return $this->collectable(\App\Post::class);
    }

    //合集对象
    public function collectable($related)
    {
        return $this->morphedByMany($related, 'collectable');
    }

    public function collectables()
    {
        return $this->hasMany(Collectable::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class);
    }

    public function articles()
    {
        return $this->hasMany(\App\Article::class);
    }

    public function hasManyArticles()
    {
        return $this->articles()->where('status', '>=', '0');
    }

    public function publishedArticles()
    {
        return $this->articles()->where('status', '>=', '0');
    }

    public function logo()
    {
        $path = empty($this->logo) ? '/images/collection.png' : $this->logo;
        if (file_exists(public_path($path))) {
            return $path;
        }
        return env('APP_URL') . $path;
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

    public static function  getCollectionByName($name,$user=null,$logo=null){
        $collection = self::firstOrCreate([
            'name' => $name ],
            [
                'logo' => $logo,
                'user_id'=>$user??getUser()->id,
                'type' => 'posts',
                'status'=>1
        ]);

        return $collection;
    }
    //添加动态到合集中
    public function collectByPostIds($post_ids){

        $this->posts()->sync($post_ids, false);
    }
    //添加动态到合集中
    public function cancelCollectByPostIds($post_ids){

        $this->posts()->detach($post_ids, false);
    }
}
