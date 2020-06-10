<?php

namespace haxibiao\content;

use App\Comment;
use App\Like;
use App\Model;
use App\User;
use haxibiao\content\Traits\PostAttrs;
use haxibiao\content\Traits\PostRepo;
use haxibiao\content\Traits\PostResolvers;
use haxibiao\media\Spider;
use haxibiao\media\Video;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Post extends Model
{
    use PostRepo;
    use PostAttrs;
    use PostResolvers;

    protected $fillable = [
        'user_id',
        'description',
        'content',
        'spider_id',
        'video_id',
        'status',
        'hot',
        'count_likes',
        'count_comments',
        'created_at',
        'updated_at',
        'review_id',
        'review_day',
    ];

    const PUBLISH_STATUS = 1;
    const PRIVARY_STATUS = 0;
    const DELETED_STATUS = -1;

    const TODAY_MAX_POST_NUM = 100000;

    public static function boot()
    {
        parent::boot();

        self::saving(function ($post) {
            $post->replaceContentBadWord();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function spider(): BelongsTo
    {
        return $this->belongsTo(Spider::class);
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likable');
    }

    public function scopePublish($query)
    {
        return $query->where('status', self::PUBLISH_STATUS);
    }

    public function scopePrivacy($query)
    {
        return $query->where('status', self::PRIVARY_STATUS);
    }

    public function scopeDeleted($query)
    {
        return $query->where('status', self::DELETED_STATUS);
    }

    public function replaceContentBadWord()
    {
        $content = $this->content;
        if (!empty($content)) {
            $content = str_replace(['#在抖音，记录美好生活#', '@抖音小助手', '抖音小助手', '抖音', '@DOU+小助手'], '', $content);
        }
        $this->content     = $content;
        $this->description = $content;
    }

    //按日期生成review_id
    public static function makeNewReviewId($reviewDay = null)
    {
        //随机范围10w,如果一天内新增内容数量超过10w，需要增加这个数值...
        $maxNum    = 100000;
        $reviewDay = is_null($reviewDay) ? today()->format('Ymd') : $reviewDay;
        $reviewId  = intval($reviewDay) * $maxNum + mt_rand(1, $maxNum - 1);
        //TODO: 如何避开新生成的这个review_id 今天已经生成过了，找个空缺的位置填充
        return $reviewId;
    }

    //旧数据补充review_id
    public function reviewId()
    {
        $reviewId = $this->review_id;
        if (is_null($reviewId)) {
            $reviewDay = is_null($this->created_at) ? null : $this->created_at->format('Ymd');
            $reviewId  = self::makeNewReviewId($reviewDay);
        }
        return $reviewId;
    }

    public static function isTodayReviewId($reviewId)
    {
        $prefix = today()->format('Ymd') . '*';
        return Str::is($prefix, $reviewId);
    }

    public static function todayMinReviewId()
    {
        $minReviewPost = Post::select('review_id')
            ->where('review_id', '>=', today()->format('Ymd') * 100000 + 1)
            ->orderBy('review_id')
            ->first();
        $reviewId = data_get($minReviewPost, 'review_id', 0);

        return $reviewId;
    }

    public static function makeTodayMaxReviewId()
    {
        $reviewDay = Post::makeNewReviewDay();
        $maxNum    = Post::TODAY_MAX_POST_NUM;

        return $reviewDay * $maxNum + $maxNum - 1;
    }

    public static function makeTodayMinReviewId()
    {
        $reviewDay = Post::makeNewReviewDay();
        $maxNum    = Post::TODAY_MAX_POST_NUM;

        return $reviewDay * $maxNum;
    }

    public static function getMaxReviewIdInDays()
    {
        $maxRviewIds = Post::selectRaw("review_day,max(review_id) as max_review_id")
            ->groupBy('review_day')
            ->latest('review_day')
            ->get();

        return $maxRviewIds;
    }

    public static function makeNewReviewDay()
    {
        return today()->format('Ymd');
    }

    //给每小时批量生成review_ids用
    public static function makeReviewIds($maxRviewId, $count)
    {
        $reviewIds = [];
        for ($i = 1; $i <= $count; $i++) {
            $reviewIds[] = $maxRviewId + $i;
        }

        //随机打乱出去分配
        shuffle($reviewIds);

        return $reviewIds;
    }

    public static function getRecommendPosts($limit = 10)
    {
        //登录
        if (checkUser()) {
            return Post::fastRecommendPosts($limit);
        }
        //游客
        return Post::getGuestPosts($limit);
    }

    public static function getGuestPosts($limit = 10)
    {
        $qb = Post::with(['video', 'user', 'user.role'])
            ->has('video')
            ->publish();
        $qb     = $qb->take($limit);
        $offset = mt_rand(0, 50); //随机感？
        $qb     = $qb->skip($offset);
        return $qb->latest('id')->get();
    }

    //兼容老接口
    public static function getOldPosts($userId, $offset, $limit)
    {
        $posts = [];
        if (is_null($userId)) {
            //视频刷
            if (checkUser()) {
                //登录
                $posts = Post::fastRecommendPosts($limit);
                return $posts;
            } else {
                //游客
                return Post::getGuestPosts($limit);
            }

        } else {
            //获取用户的视频动态
            $posts = Post::where('user_id', $userId)
                ->publish()
                ->latest('id')
                ->skip($offset)
                ->take($limit)
                ->get();
        }

        return $posts;
    }
}
