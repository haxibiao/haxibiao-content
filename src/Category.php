<?php

namespace Haxibiao\Content;

use App\Post;
use Haxibiao\Breeze\Model;
use Haxibiao\Breeze\Traits\HasFactory;
use Haxibiao\Content\Traits\CategoryAttrs;
use Haxibiao\Content\Traits\CategoryRepo;
use Haxibiao\Content\Traits\CategoryResolvers;
use Haxibiao\Content\Traits\WithCms;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory;
    use CategoryResolvers;
    use CategoryAttrs;
    use CategoryRepo;

    use WithCms;
    use \Haxibiao\Content\Traits\Stickable;

    const LOGO_PATH = '/images/category.logo.jpg';

    /**
     * 状态机：专题的3个常用状态
     */
    const STATUS_TRASH  = -1; // 删除
    const STATUS_DRAFT  = 0; // 草稿（默认）
    const STATUS_PUBLIC = 1; // 公开

    protected $guarded = [];

    protected $table = 'categories';

    public function getMorphClass()
    {
        return 'categories';
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class);
    }

    public function users()
    {
        return $this->belongsToMany('App\User');
    }

    public function admins()
    {
        return $this->belongsToMany('App\User')->wherePivot('is_admin', 1)->withTimestamps();
    }

    public function authors()
    {
        //投稿了发生了关联，就算专题的编辑用户
        return $this->users();
    }

    public function videoArticles()
    {
        return $this->categorizable(\App\Article::class)
            ->where('articles.type', 'video');
    }

    public function articles()
    {
        return $this->categorizable(\App\Article::class)
            ->withPivot('submit')
            ->withTimestamps()
            ->orderBy('pivot_updated_at', 'desc')
            ->exclude(['body']);
    }

    public function newRequestArticles()
    {
        return $this->articles()
            ->wherePivot('submit', '待审核')
            ->withPivot('updated_at');
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * @deprecated 今后主要用posts关系直接获取动态
     */
    public function videoPosts()
    {
        return $this->articles()->where('type', 'video');
    }

    /**
     * @deprecated 今后主要用posts关系直接获取动态
     */
    public function containedVideoPosts()
    {
        return $this->categorizable(\App\Article::class)
            ->where('articles.type', 'video');
    }

    public function requestedInMonthArticles()
    {
        return $this->categorizable(\App\Article::class)
            ->wherePivot('created_at', '>', \Carbon\Carbon::now()->addDays(-90))
            ->withPivot('submit', 'created_at')
            ->withTimestamps()
            ->orderBy('pivot_created_at', 'desc');
    }

    public function publishedWorks(): BelongsToMany
    {
        //FIXME:暂时兼容一下haxibiao博客
        if (config('app.name') == 'haxibiao') {
            return $this->belongsToMany('App\Article')
                ->where('articles.status', '>', Article::STATUS_REVIEW)
                ->wherePivotIn('submit', ['已收录', 1])
                ->withPivot('submit')
                ->withTimestamps();
        }

        return $this->categorizable(\App\Article::class)
            ->where('articles.status', '>', Article::STATUS_REVIEW)
            ->wherePivotIn('submit', ['已收录', 1])
            ->withPivot('submit')
            ->withTimestamps();
    }

    public function hasManyArticles()
    {
        return $this->hasMany('App\Article', 'category_id', 'id');
    }

    public function publishedArticles()
    {
        return $this->articles()
            ->where('articles.status', '>', Article::STATUS_REVIEW)
            ->wherePivot('submit', '已收录');
    }

    public function parent()
    {
        return $this->belongsTo(App\Category::class, 'parent_id');
    }

    public function subCategory()
    {
        return $this->hasMany(App\Category::class, 'parent_id', 'id');
    }

    public function issues()
    {
        return $this->categorizable(\App\Issue::class);
    }

    public function follows()
    {
        return $this->morphMany(\App\Follow::class, 'followable');
    }

    public function categorizable($related)
    {
        return $this->morphedByMany($related, 'categorizable', 'categorizables', 'category_id');
    }

    public function related()
    {
        return $this->hasMany(Categorizable::class);
    }
}
