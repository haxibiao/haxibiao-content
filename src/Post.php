<?php

namespace Haxibiao\Content;

use App\User;
use Carbon\Carbon;
use Haxibiao\Breeze\Model;
use Haxibiao\Breeze\Traits\HasFactory;
use Haxibiao\Content\Constracts\Collectionable;
use Haxibiao\Content\Traits\Contentable;
use Haxibiao\Content\Traits\FastRecommendStrategy;
use Haxibiao\Content\Traits\PostAttrs;
use Haxibiao\Content\Traits\PostOldPatch;
use Haxibiao\Content\Traits\PostRepo;
use Haxibiao\Content\Traits\PostResolvers;
use Haxibiao\Content\Traits\WithCms;
use Haxibiao\Helpers\Traits\Searchable;
use Haxibiao\Media\Image;
use Haxibiao\Media\Movie;
use Haxibiao\Media\Spider;
use Haxibiao\Media\Video;
use Haxibiao\Sns\Traits\WithSns;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class Post extends Model implements Collectionable
{
    use HasFactory;
    use SoftDeletes;
    use PostRepo;
    use PostAttrs;
    use PostResolvers;
    use Searchable;

    use FastRecommendStrategy;
    use PostOldPatch;
    use WithSns;
    use Contentable;

    use WithCms;
    use \Haxibiao\Content\Traits\Stickable;

    public function getMorphClass()
    {
        return 'posts';
    }

    protected $searchable = [
        'columns' => [
            'posts.description'  => 2,
            'taggables.tag_name' => 1,
        ],
        'joins'   => [
            'taggables' => [
                ['taggables.taggable_id', 'posts.id'],
                ['taggables.taggable_type', 'posts'],
            ],
        ],
    ];

    protected $guarded = [];

    protected $table = 'posts';

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
        //更新时触发--方便保证spider中有相关数据
        self::updating(function ($post) {
            // 公司用户展示权利交给马甲号
            $post->transferToVest();
        });
    }
    public function image(): BelongsTo
    {
        return $this->belongsTo(Image::class, 'image_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    //定位功能
    public function locations(): MorphMany
    {
        return $this->morphMany(Location::class, 'located');
    }

    public function getLocationAttribute()
    {
        return $this->locations->last();
    }
    public function getLocationDescAttribute()
    {
        return $this->locations->last() ? $this->locations->last()->description : null;
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

    public function recommended()
    {
        return $this->hasOne(PostRecommended::class, 'post_id');
    }

    //统一使用imageables表
    public function images()
    {
        return $this->morphToMany(Image::class, 'imageable')->withTimestamps();
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
        $description = $this->description;
        if (!empty($description)) {
            $description       = str_replace(['#在抖音，记录美好生活#', '@抖音小助手', '抖音小助手', '抖音', '@DOU+小助手', '快手', '#快手创作者服务中心', ' @快手小助手', '#快看'], '', $description);
            $this->description = $description;
        }
    }

    public static function getRecommendPosts($limit = 4, $query = null, $scope = null)
    {
        //登录
        if (currentUser()) {
            return Post::fastRecommendPosts($limit, $query, $scope);
        }
        //游客
        return Post::getGuestPosts($limit);
    }

    public static function getGuestPosts($limit = 5)
    {
        $withRelationList = ['video', 'user'];
        if (class_exists("App\\Role", true)) {
            $withRelationList = array_merge($withRelationList, ['user.role']);
        }
        $qb = \App\Post::with($withRelationList)
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
            if (currentUser()) {
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
        if (is_null($this->review_id)) {
            $this->review_id  = str_replace("-", "", Carbon::today()->toDateString()) . substr($new_num, 1, 5);
            $this->review_day = str_replace("-", "", Carbon::today()->toDateString());
        }
    }

    public static function getStatuses()
    {
        return [
            self::PUBLISH_STATUS => '已发布',
            self::PRIVARY_STATUS => '草稿箱',
            self::DELETED_STATUS => '已删除',
        ];
    }

    /**
     * 展示转移交给马甲用户
     */
    public function transferToVest()
    {
        $user    = User::find($this->user_id);
        $ownerId = data_get($this, 'owner_id');
        if (empty($user) || !empty($ownerId)) {
            return;
        }
        // 系统是否开启马甲号逻辑
        $postOpenVest = config('haxibiao-content.post_open_vest', false);
        if (!$postOpenVest) {
            return;
        }

        // 数据库不完整
        if (!\Illuminate\Support\Facades\Schema::hasColumn('posts', 'owner_id')) {
            return;
        }
        // 动态是否开启默认生成合集
        $postOpenCollection = config('haxibiao-content.post_open_collection', true);
        if ($postOpenCollection) {
            // 有合集的抖音视频&&已经分配过马甲号 不分发马甲号
            $spiderId = data_get($this, 'spider_id');
            if ($spiderId) {
                $spider  = Spider::find($spiderId);
                $mixInfo = data_get($spider, 'data.raw.item_list.0.mix_info');
                if ($mixInfo && $this->owner_id) {
                    $this->user_id = $this->owner_id;
                    return;
                }
                if ($mixInfo) {
                    return;
                }
            }
        }

        // 普通用户不执行马甲逻辑
        $roleId = $user->role_id;
        if (!in_array($roleId, [User::EDITOR_STATUS, User::ADMIN_STATUS])) {
            return;
        }

        $userIds = User::where('role_id', User::VEST_STATUS)->pluck('id')->toArray();
        $userIds = array_merge($userIds, [$user->id]);
        $vestId  = array_random($userIds);
        if ($vestId) {
            $this->owner_id = $user->id;
            $this->user_id  = $vestId;
        }
    }

    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    const SHARE_DOIYIN_VIDEO_REWARD = 10;
    const MIN_HOT_RECOMMEND_COUNT   = 5;

    public function postData(): HasOne
    {
        return $this->hasOne(PostData::class);
    }

    public function syncPost($Ids, $postable, $type = false)
    {
        $modelStr = Relation::getMorphedModel($postable);
        $modelIds = $modelStr::select('id')->whereIn('id', $Ids)->get()->pluck('id')->toArray();
        $this->postable($modelStr)
            ->sync($modelIds, $type);

        // 同步到source_type字段
        if (!empty($this->source_type)) {
            $this->update(['source_type' => $postable]);
        }

        return $this;
    }

    public function postable($related)
    {
        return $this->morphedByMany($related, 'postable')
            ->withTimestamps();
    }

    public function movies()
    {
        return $this->postable(\App\Movie::class);
    }

    public function resolveSearchPosts($root, array $args, $context)
    {
        $userId       = data_get($args, 'user_id');
        $tagId        = data_get($args, 'tag_id');
        $collectionId = data_get($args, 'collection_id');
        $type         = data_get($args, 'type');
        return static::publish()->search(data_get($args, 'query'))
            ->when($type == 'VIDEO', function ($q) use ($userId) {
                return $q->whereNotNull('video_id');
            })->when($type == 'IMAGE', function ($q) use ($userId) {
            return $q->whereNull('video_id');
        })->when($userId, function ($q) use ($userId) {
            return $q->where('posts.user_id', $userId);
        })->when($tagId, function ($q) use ($tagId) {
            return $q->whereHas('tags', function ($q) use ($tagId) {
                $q->where('tags.id', $tagId);
            });
        })->when($collectionId, function ($q) use ($collectionId) {
            return $q->whereHas('collections', function ($q) use ($collectionId) {
                $q->where('collections.id', $collectionId);
            });
        })->with('video');
    }

    public function resolveUserPosts($root, $args, $context, $info)
    {
        $filter = data_get($args, 'filter');

        if ($filter == 'spider') {
            return static::posts($args['user_id'])->whereNotNull('spider_id');
        } elseif ($filter == 'normal') {
            return static::posts($args['user_id'])->whereNull('spider_id');
        }
        return static::posts($args['user_id']);
    }

    public function resolveUpdatePost($root, $args, $context, $info)
    {
        $postId = data_get($args, 'post_id');
        $post   = static::findOrFail($postId);
        $post->update(
            Arr::only($args, ['content', 'description'])
        );

        // 同步标签
        $tagNames = data_get($args, 'tag_names', []);
        $post->retagByNames($tagNames);

        return $post;
    }

    //关注用户的收藏列表
    public function resolveFollowPosts($rootValue, array $args, $context, $resolveInfo)
    {
        $filter = data_get($args, 'filter');
        $user   = getUser();
        //2.获取用户关注列表
        $followedUserIds = $user->follows()->pluck('followable_id');
        //3.获取关注用户发布的视频
        $qb = static::whereNotNull('video_id')
            ->whereIn('user_id', $followedUserIds)
            ->orderByDesc('id');

        if (in_array(
            ['video', 'collections', 'images'],
            data_get($resolveInfo->getFieldSelection(1), 'data')
        )) {
            $qb->with(['video', 'collections', 'images']);
        }

        if ($filter == 'spider') {
            return $qb->whereNotNull('spider_id');
        } elseif ($filter == 'normal') {
            return $qb->whereNull('spider_id');
        }
        return $qb;
    }

    public function postByVideoId($rootValue, array $args, $context, $resolveInfo)
    {
        $videoId = data_get($args, 'video_id');
        return \App\Post::where('video_id', $videoId)->first();
    }

    public function resolveRecommendPosts($root, $args, $context, $info)
    {
        app_track_event("首页", "获取学习视频");

        $user = currentUser();

        if (!is_null($user)) {
            return static::hotRecommendPosts($user->id);
        } else {
            return static::getRecommendPosts();
        }
    }

    public static function fastCreatePost($videoId, $description, $status = Post::PRIVARY_STATUS, $source = [])
    {
        $video = Video::find($videoId);
        throw_if(is_null($video), UserException::class, '发布失败,视频不存在!');

        $data = array_merge([
            'video_id'    => $video->id,
            'content'     => $description,
            'description' => $description,
            'user_id'     => $video->user_id,
            'status'      => $status,
        ], $source);
        $post = Post::create($data);

        return $post;
    }

    public function reScore()
    {
        $hot   = $this->hot;
        $hot   = $hot > 0 ? $hot : 1;
        $score = bcdiv($this->count_comments, $hot, 7) + bcdiv($this->count_likes, $hot, 7);
        $score = intval($score * 100000);

        // 优先去推荐视频题
        if ($this->source_type == 'questions') {
            $score += mt_rand(1000, 10000);
        }

        // 优先去推荐长视频
        if ($this->source_type == 'movies' || $this->movies()->count() > 0) {
            $score += mt_rand(1500, 10000);
        }

        $this->score = $score;
    }

    public function hasColumn($column)
    {
        return in_array($column, config('db-table.1' . $this->getTable(), []));
    }

    public function fillSourceField()
    {
        $hasSourceField = $this->hasColumn('source_id') && $this->hasColumn('source_type');
        $isFilled       = !empty($this->source_id) && !empty($this->source_type);
        $emptySpiderId  = empty($this->spider_id);
        if (!$hasSourceField || $isFilled || $emptySpiderId) {
            return;
        }

        $this->source_id   = $this->spider_id;
        $this->source_type = 'spiders';

    }
}
