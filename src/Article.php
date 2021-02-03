<?php

namespace Haxibiao\Content;

use Haxibiao\Breeze\Model;
use Haxibiao\Content\Constracts\Collectionable;
use Haxibiao\Content\Traits\ArticleAttrs;
use Haxibiao\Content\Traits\ArticleRepo;
use Haxibiao\Content\Traits\ArticleResolvers;
use Haxibiao\Content\Traits\Contentable;
use Haxibiao\Sns\Traits\WithSns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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

    protected $guarded = ['api_token'];

    protected $table = 'articles';

    protected static function boot()
    {
        parent::boot();
        static::observe(Observers\ArticleObserver::class);
    }

    //提交状态
    const REFUSED_SUBMIT   = -1; //已拒绝
    const REVIEW_SUBMIT    = 0; //待审核
    const SUBMITTED_SUBMIT = 1; //已收录

    //  动态状态
    const STATUS_REFUSED = -1;
    const STATUS_REVIEW  = 0;
    const STATUS_ONLINE  = 1;

    protected $touches = ['category', 'collection', 'categories'];

    protected $dates = ['edited_at', 'delay_time', 'commented'];

    protected $casts = [
        'json' => 'object',
    ];

    public function getMorphClass()
    {
        return 'articles';
    }

    public function issue()
    {
        return $this->belongsTo('App\Issue');
    }

    //relations
    public function user()
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
        return $query->where('status', 1);
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
