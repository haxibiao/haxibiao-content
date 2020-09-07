<?php

namespace Haxibiao\Content;

use App\Comment;
use App\Image;
use App\Like;
use App\Model;
use App\User;
use App\Video;
use Carbon\Carbon;
use Haxibiao\Content\Traits\Categorizable;
use Haxibiao\Content\Traits\PostAttrs;
use Haxibiao\Content\Traits\PostOldPatch;
use Haxibiao\Content\Traits\PostRepo;
use Haxibiao\Content\Traits\PostResolvers;
use Haxibiao\Media\Spider;
use Haxibiao\Media\Traits\WithImage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Post extends Model
{
    use SoftDeletes;

    use PostRepo;
    use PostAttrs;
    use PostResolvers;
    use WithImage;
    use Categorizable;
    use PostOldPatch;

    public function getMorphClass()
    {
        return 'posts';
    }

    protected $guarded = [];

    const PUBLISH_STATUS = 1;
    const PRIVARY_STATUS = 0;
    const DELETED_STATUS = -1;

    const TODAY_MAX_POST_NUM = 100000;

    //安保联盟App中标识视频类别，其他项目没有用到
    const STUDY = 1;
    //娱乐视频
    const PLAY = 2;
    //固定视频
    const FIRST = 3;

    public static function boot()
    {
        parent::boot();

        //保存时触发
        self::saving(function ($post) {
            $post->replaceContentBadWord();

            //设置review_id 和 review_day
            $post->initReviewIdAndReviewDay();
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

    public function images()
    {
        return $this->morphToMany(Image::class, 'imageable')->withTimestamps();
    }

    public function favorite()
    {
        return $this->morphMany(\App\Post::class, 'faved_type');
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
            $content           = str_replace(['#在抖音，记录美好生活#', '@抖音小助手', '抖音小助手', '抖音', '@DOU+小助手'], '', $content);
            $this->content     = $content;
            if(!$this->description){
                $this->description = $content;
            }
        }
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
        $minReviewPost = static::select('review_id')
            ->where('review_id', '>=', today()->format('Ymd') * 100000 + 1)
            ->orderBy('review_id')
            ->first();
        $reviewId = data_get($minReviewPost, 'review_id', 0);

        return $reviewId;
    }

    public static function makeTodayMaxReviewId()
    {
        $reviewDay = static::makeNewReviewDay();
        $maxNum    = Post::TODAY_MAX_POST_NUM;

        return $reviewDay * $maxNum + $maxNum - 1;
    }

    public static function makeTodayMinReviewId()
    {
        $reviewDay = static::makeNewReviewDay();
        $maxNum    = Post::TODAY_MAX_POST_NUM;

        return $reviewDay * $maxNum;
    }

    public static function getMaxReviewIdInDays()
    {
        $maxRviewIds = static::selectRaw("review_day,max(review_id) as max_review_id")
            ->whereStatus(1) //只考虑已上架发布的动态
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

    public static function getRecommendPosts($limit = 4)
    {
        //登录
        if (checkUser()) {
            return static::fastRecommendPosts($limit);
        }
        //游客
        return static::getGuestPosts($limit);
    }

    public static function getGuestPosts($limit = 5)
    {
        $withRelationList = ['video', 'user'];
        if(class_exists("App\\Role", true)){
            $withRelationList = array_merge($withRelationList,['user.role']);
        }
        $qb = static::with($withRelationList)
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
                $posts = static::fastRecommendPosts($limit);
                return $posts;
            } else {
                //游客
                return static::getGuestPosts($limit);
            }
        } else {
            //获取用户的视频动态
            $posts = static::where('user_id', $userId)
                ->publish()
                ->latest('id')
                ->skip($offset)
                ->take($limit)
                ->get();
        }

        return $posts;
    }

    /**
     * 设置 posts 表中 review_id 与 review_day
     *
     * note: 它们两个是排重算法依赖的值。拼接规则如下:
     * review_id: 当前日期 + 当前动态，对应的视频，今日的发布顺序. 例如:20200619 00001
     * review_day: 当前日期. 例如:20200619
     */
    public function initReviewIdAndReviewDay()
    {
        //模板值，拼接出想要的review_id
        $temp_num = 100001;

        //今日Posts新增数量，用于拼接review_id
        $count = DB::table('videos')
            ->whereBetWeen('created_at', [today(), today()->addDay()])->count();

        $new_num = $count + $temp_num;

        //赋值
        $this->review_id  = str_replace("-", "", Carbon::today()->toDateString()) . substr($new_num, 1, 5);
        $this->review_day = str_replace("-", "", Carbon::today()->toDateString());
    }

    public static function getStatuses()
    {
        return [
            self::PUBLISH_STATUS => '已发布',
            self::PRIVARY_STATUS => '草稿箱',
            self::DELETED_STATUS => '已删除',
        ];
    }
}
