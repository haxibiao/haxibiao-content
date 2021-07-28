<?php

namespace Haxibiao\Content;

use Haxibiao\Breeze\Model;
use Haxibiao\Breeze\Traits\HasFactory;
use Haxibiao\Content\Constracts\Collectionable;
use Haxibiao\Content\Traits\ArticleAttrs;
use Haxibiao\Content\Traits\ArticleRepo;
use Haxibiao\Content\Traits\ArticleResolvers;
use Haxibiao\Content\Traits\Contentable;
use Haxibiao\Content\Traits\WithCms;
use Haxibiao\Sns\Traits\WithSns;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model implements Collectionable
{
    use HasFactory;
    use ArticleRepo;
    use ArticleResolvers;
    use ArticleAttrs;
    use SoftDeletes;
    use Contentable;
    use WithSns;

    use WithCms;
    use \Haxibiao\Content\Traits\Stickable;

    protected $guarded = ['api_token'];

    protected $table = 'articles';

    protected static function boot()
    {
        parent::boot();
        static::observe(Observers\ArticleObserver::class);
    }

    //文章类型
    const ARTICLE = 'article';  //文章
    const MEETUP  = 'meetup'; //约单

    //提交状态
    const REFUSED_SUBMIT   = -1; //已拒绝
    const REVIEW_SUBMIT    = 0; //待审核
    const SUBMITTED_SUBMIT = 1; //已收录

	/**
	 * 状态机
	 */
    const STATUS_REFUSED = -1; // 私密
    const STATUS_REVIEW  = 0;  // 审核中（默认）
    const STATUS_ONLINE  = 1;  // 公开

    protected $touches = ['category', 'collection', 'categories'];

    protected $dates = ['edited_at', 'delay_time', 'commented'];

    protected $casts = [
        'json' => 'object',
    ];

    public function getMorphClass()
    {
        return 'articles';
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo('App\Movie');
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo('App\Video');
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo('App\Issue');
    }

    //relations
    public function user(): BelongsTo
    {
        return $this->belongsTo('App\User');
    }

    public function relatedVideoPostsQuery()
    {
        return Article::with(['video' => function ($query) {
            //过滤软删除的video
            $query->whereStatus(1);
        }])->where('type', 'video')
            ->whereIn('category_id', $this->categories->pluck('id'));
    }

    public function scopePublish($query)
    {
        return $query->where('status', static::STATUS_ONLINE);
    }

    public static function getSubmitStatus()
    {
        return [
            self::SUBMITTED_SUBMIT => '已收录',
            self::REVIEW_SUBMIT    => '待审核',
            self::REFUSED_SUBMIT   => '已拒绝',
        ];
    }
}
