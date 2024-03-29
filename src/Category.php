<?php

namespace Haxibiao\Content;

use App\Post;
use Haxibiao\Breeze\Model;
use Haxibiao\Breeze\Traits\HasFactory;
use Haxibiao\Breeze\Traits\ModelHelpers;
use Haxibiao\Content\Traits\CategoryAttrs;
use Haxibiao\Content\Traits\CategoryRepo;
use Haxibiao\Content\Traits\CategoryResolvers;
use Haxibiao\Content\Traits\CategoryScopes;
use Haxibiao\Content\Traits\WithCms;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory;
    use CategoryResolvers;
    use CategoryAttrs;
    use CategoryRepo;
    use CategoryScopes;
    use ModelHelpers;

    use WithCms;
    use \Haxibiao\Content\Traits\Stickable;

    const LOGO_PATH = '/images/category.logo.jpg';

    protected $casts = [
        'ranks'                  => 'array',
        'answers_count_by_month' => 'array',
    ];

    //原question包的常量
    const ALLOW_SUBMIT    = 1; //允许所有用户出题
    const AUTO_SUBMIT     = 0; //自动允许资深用户出题
    const DISALLOW_SUBMIT = -1; //禁止所有用户出题

    const PRIVACY = 0; //隐藏
    const DELETED = -1; //删除

    // 暂时写死,这个学习视频分类的ID
    const RECOMMEND_VIDEO_QUESTION_CATEGORY = 153;

    // 分类类型
    const QUESTION_TYPE_ENUM       = 0;
    const ARTICLE_TYPE_ENUM        = 1;
    const FORK_QUESTION_TYPE_ENUM  = 2;
    const SCORE_QUESTION_TYPE_ENUM = 3;
    const MUSIC_TYPE_ENUM          = 4;

    const GROUPS = [
        1 => '知识百科',
        2 => '职业公考',
        3 => '趣味益智',
        4 => '学科考试',
    ];

    /**
     * 状态机：专题的3个常用状态
     */
    const STATUS_TRASH   = -1; // 删除
    const STATUS_DRAFT   = 0; // 草稿（默认）
    const STATUS_PUBLISH = 1; // 公开

    //兼容答赚
    const TRASH        = -1; // 删除
    const DRAFT        = 0; // 草稿（默认）
    const PUBLISH      = 1; // 公开
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
        return $this->belongsToMany('App\User')
            ->withPivot(['max_review_id', 'min_review_id', 'correct_count']);
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
        if (in_array(config('app.name'), ['haxibiao', 'datizhuanqian'])) {
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
        return $this->belongsTo(\App\Category::class, 'parent_id');
    }

    public function subCategory()
    {
        return $this->hasMany(\App\Category::class, 'parent_id', 'id');
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

    //下面是question包的category
    /**
     * =====================================
     **/

    public function children()
    {
        return $this->hasMany(\App\Category::class, 'parent_id');
    }

    public function publishedChildren()
    {
        return $this->children()->whereStatus(self::PUBLISH);
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function audios()
    {
        return $this->categorizable(\App\Audio::class)
            ->withTimestamps()
            ->orderBy('pivot_updated_at', 'desc');
    }

    public static function getAllowSubmits()
    {
        return [
            self::ALLOW_SUBMIT    => '允许所有用户出题',
            self::AUTO_SUBMIT     => '自动允许资深用户出题(未实现)',
            self::DISALLOW_SUBMIT => '禁止所有用户出题',
        ];
    }

    public static function getStatuses()
    {
        return [
            self::PUBLISH => '公开',
            self::PRIVACY => '下架',
            self::DELETED => '删除',
        ];
    }

    public function questions()
    {
        return $this->hasMany(\App\Question::class);
    }

    public function forkQuestions()
    {
        return $this->hasMany(\App\ForkQuestion::class);
    }

    public function hotQuestions($count = 10)
    {
        return $this->questions()->publish()->take($count)->get();
    }

    public function comments()
    {
        return $this->morphMany(\App\Comment::class, 'commentable');
    }

    public function forkAnswers()
    {
        return $this->hasMany(\App\ForkAnswer::class, 'fork_question_id');
    }

    public function likes()
    {
        return $this->morphMany(\App\Like::class, 'likable');
    }

    public function notLikes()
    {
        return $this->morphMany(\App\NotLike::class, 'not_likable');
    }

    public function publishedQuestions()
    {
        return $this->hasMany(\App\Question::class)->publish();
    }

    //nova
    public static function getOrders()
    {
        return [
            '正序' => 'asc',
            '倒叙' => 'desc',
        ];
    }

}
