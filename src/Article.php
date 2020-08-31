<?php

namespace Haxibiao\Content;

use App\Model;
use Haxibiao\Content\Traits\ArticleAttrs;
use Haxibiao\Content\Traits\ArticleRepo;
use Haxibiao\Content\Traits\ArticleResolvers;
use Haxibiao\Content\Traits\Categorizable;
use Haxibiao\Media\Traits\WithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use ArticleRepo;
    use ArticleResolvers;
    use ArticleAttrs;
    use SoftDeletes;
    use Categorizable;
    use WithMedia;

    protected $casts = [
        'json' => 'object',
    ];

    protected $fillable = [
        'title',
        'keywords',
        'description',
        'author',
        'author_id',
        'user_id',
        'category_id',
        'collection_id',
        'body',
        'count_words',
        'is_top',
        'status',
        'source_url',
        'hits',
        'count_likes',
        'count_comments',
        'type',
        'video_id',
        'slug',
        'submit',
        'cover_path',
    ];

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

    public function issue()
    {
        return $this->belongsTo('App\Issue');
    }

    //relations
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function video()
    {
        return $this->belongsTo('App\Video');
    }

    //主文集
    public function collection()
    {
        return $this->belongsTo('App\Collection');
    }

    public function favorites()
    {
        return $this->morphMany(\App\Favorite::class, 'faved');
    }

    public function comments()
    {
        return $this->morphMany(\App\Comment::class, 'commentable');
    }

    public function likes()
    {
        return $this->morphMany(\App\Like::class, 'liked');
    }

    public function tags()
    {
        return $this->morphToMany('App\Tag', 'taggable');
    }

    public function tips()
    {
        return $this->morphMany(\App\Tip::class, 'tipable');
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
