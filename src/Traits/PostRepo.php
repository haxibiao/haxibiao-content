<?php

namespace haxibiao\content\Traits;

use App\Ip;
use App\Gold;
use App\Image;
use App\Video;
use App\Visit;
use App\Action;
use Carbon\Carbon;
use App\Jobs\ProcessVod;
use haxibiao\content\Post;
use Illuminate\Support\Arr;
use Yansongda\Supports\Str;
use App\Exceptions\GQLException;
use haxibiao\helpers\BadWordUtils;
use Illuminate\Support\Facades\DB;
use haxibiao\content\PostRecommend;
use Illuminate\Support\Facades\Log;
use haxibiao\content\Jobs\PublishNewPosts;

trait PostRepo
{

    //创建post（video和image），不处理issue问答创建了
    public function resolveCreateContent($root, array $args, $context)
    {
        if (BadWordUtils::check(Arr::get($args, 'body'))) {
            throw new GQLException('发布的内容中含有包含非法内容,请删除后再试!');
        }
        //参数格式化
        $inputs = [
            'body'         => Arr::get($args, 'body'),
            'gold'         => Arr::get($args, 'issueInput.gold', 0),
            'category_ids' => Arr::get($args, 'category_ids', null),
            'images'       => Arr::get($args, 'images', null),
            'video_id'     => Arr::get($args, 'video_id', null),
            'qcvod_fileid' => Arr::get($args, 'qcvod_fileid', null),
        ];
        return $this->createPost($inputs);
    }

    /**
     * 创建动态
     * body:    文字描述
     * category_ids:    话题
     * images:  base64图片
     * video_id: 视频ID
     */
    public static function CreatePost($inputs)
    {

        DB::beginTransaction();

        try {
            $user = getUser();

            $todayPublishVideoNum = Post::where("user_id", $user->id)
                ->whereNotNull('video_id')
                ->whereDate('created_at', Carbon::now())->count();

            //角色为0的用户发布限制10个，其余的角色大部分是内部人员，方便测试不限制
            if ($todayPublishVideoNum == 10 || $user->role_id < 1) {
                throw new GQLException('每天只能发布10个视频动态!');
            }

            if ($user->isBlack()) {
                throw new GQLException('发布失败,你以被禁言');
            }

            //带视频
            if ($inputs['video_id'] || $inputs['qcvod_fileid']) {
                if ($inputs['qcvod_fileid']) {
                    $qcvod_fileid = $inputs['qcvod_fileid'];
                    $video        = Video::firstOrNew([
                        'qcvod_fileid' => $qcvod_fileid,
                    ]);
                    $video->qcvod_fileid = $qcvod_fileid;
                    $video->user_id = $user->id;
                    // qc vod api 获取video cdn url ... 耗时间... 后面job处理了
                    //FIXME: 这里先用黑屏占位，后面通过job和fileid更新封面和视频? 还是从前端返回cdnurl 就可以秒播放了
                    $video->path = 'http://1254284941.vod2.myqcloud.com/e591a6cavodcq1254284941/74190ea85285890794946578829/f0.mp4';
                    // $video->cover = '...'; //TODO: 待王彬新 sdk 提供封面cdn url
                    $video->title = Str::limit($inputs['body'], 50);
                    $video->save();
                    //创建article
                    $post              = new Post();
                    $post->user_id        = $user->id;
                    $post->video_id    = $video->id;
                    $post->status      = Post::PRIVARY_STATUS; //vod视频动态刚发布时是草稿状态
                    $post->content     = Str::limit($inputs['body'], 100);
                    $post->review_id   = Post::makeNewReviewId();
                    $post->review_day  = Post::makeNewReviewDay();
                    $post->save();
                    ProcessVod::dispatch($video);

                    // 记录用户操作
                    Action::createAction('articles', $post->id, $post->user->id);
                    Ip::createIpRecord('articles', $post->id, $post->user->id);
                } else if ($inputs['video_id']) {
                    $video   = Video::findOrFail($inputs['video_id']);
                    $post = $video->post;
                    if (!$post) {
                        $post = new Post();
                    }
                    $post->content     = Str::limit($inputs['body'], 100);
                    $post->review_id   = Post::makeNewReviewId();
                    $post->review_day  = Post::makeNewReviewDay();
                    $post->video_id    = $video->id; //关联上视频
                    $post->save();
                }
            } else {
                //带图片
                $post              = new Post();
                $post->content     = Str::limit($inputs['body'], 100);
                $post->user_id     = $user->id;
                $post->save();

                if ($inputs['images']) {
                    foreach ($inputs['images'] as $image) {
                        $image = Image::saveImage($image);
                        $post->images()->sync($image);
                    }

                    $post->cover_path = $post->images()->first()->path;
                    $post->save();
                }
            }
            DB::commit();
            app_track_user('发布动态', 'post');
            return $post;
        } catch (\Exception $ex) {
            if ($ex->getCode() == 0) {
                Log::error($ex->getMessage());
                DB::rollBack();
                throw new GQLException('程序小哥正在加紧修复中!');
            }
            throw new GQLException($ex->getMessage());
        }
    }

    /**
     * @deprecated
     */
    public static function getHotPosts($user, $limit = 10, $offset = 0)
    {
        $hasLogin = !is_null($user);
        $limit    = $limit >= 10 ? 8 : 4;

        //构建查询
        $qb = Post::with(['video', 'user', 'user.role'])->has('video')->publish()
            ->orderByDesc('review_id')
            ->take($limit);
        //存在用户
        if ($hasLogin) {
            //过滤掉自己 和 不喜欢用户的作品
            $notLikIds   = $user->notLikes()->ByType('users')->get()->pluck('not_likable_id')->toArray();
            $notLikIds[] = $user->id;
            $qb          = $qb->whereNotIn('user_id', $notLikIds);

            //排除浏览过的视频
            $visitVideoIds = Visit::ofType('posts')->ofUserId($user->id)->get()->pluck('visited_id');
            if (!is_null($visitVideoIds)) {
                $qb = $qb->whereNotIn('id', $visitVideoIds);
            }
        } else {
            //游客浏览翻页
            //访客第一页随机略过几个视频
            $offset = $offset == 0 ? mt_rand(0, 50) : $offset;
            $qb     = $qb->skip($offset);
        }
        //获取数据
        $posts = $qb->get();

        if ($hasLogin) {
            //喜欢状态
            $posts = Post::likedPosts($user, $posts);

            //关注动态的用户
            $posts = Post::followedPostsUsers($user, $posts);

            //批量插入
            Visit::saveVisits($user, $posts, 'posts');
        }

        //第二页混淆一下 防止重复的靠前
        // if ($offset > 0) {
        //     $posts = $posts->shuffle();
        // }

        //混合广告视频
        $mixPosts = Post::mixPosts($posts);

        return $mixPosts;
    }

    /**
     * 混合广告视频
     * @param $posts Collection
     */
    public static function mixPosts($posts)
    {
        //不够4个不参入广告
        if ($posts->count() < 4) {
            return $posts;
        }
        $mixPosts = [];
        $index    = 0;
        foreach ($posts as $post) {
            $index++;
            $mixPosts[] = $post;
            if ($index % 4 == 0) {
                //每隔4个插入一个广告
                $adPost        = clone $post;
                $adPost->id    = random_str(7);
                $adPost->is_ad = true;
                $mixPosts[]    = $adPost;
            }
        }

        return $mixPosts;
    }

    public static function likedPosts($user, $posts)
    {
        $postIds = $posts->pluck('id');
        if (count($postIds) > 0) {
            $likedIds = $user->likedTableIds('posts', $postIds);
            //更改liked状态
            $posts->each(function ($post) use ($likedIds) {
                $post->liked = $likedIds->contains($post->id);
            });
        }

        return $posts;
    }

    public static function followedPostsUsers($user, $posts)
    {
        $userIds = $posts->pluck('user_id');
        if (count($userIds) > 0) {
            $followedUserIds = $user->followedUserIds($userIds);
            //更改liked状态
            $posts->each(function ($post) use ($followedUserIds) {
                $postUser = $post->user;
                if (!is_null($postUser)) {
                    $postUser->followed_user_status = $followedUserIds->contains($postUser->id);
                }
            });
        }

        return $posts;
    }

    /**
     * 目前最简单的错日排重推荐视频算法(FastRecommend)，人人可以看最新，随机，过滤，不重复的视频流了
     *
     * @param int $limit
     * @return array
     */
    public static function fastRecommendPosts($limit = 4)
    {
        $user = getUser(); //必须登录

        //把每天的最大指针拿进一个数组 //TODO: 可以缓存1小时
        $maxReviewIdInDays = Post::getMaxReviewIdInDays();

        //构建查询
        $qb_published = Post::has('video')->with(['video', 'user', 'user.role'])->publish();
        $qb           = $qb_published;

        //登录用户

        //1.过滤 过滤掉自己 和 不喜欢用户的作品
        //FIXME: 答妹等喜欢还没notlike表的
        $notLikIds = [];
        if (class_exists("App\NotLike")) {
            $notLikIds = $user->notLikes()->ByType('users')->get()->pluck('not_likable_id')->toArray();
        }
        $notLikIds[] = $user->id; //默认不喜欢刷到自己的视频动态
        $qb          = $qb->whereNotIn('user_id', $notLikIds);

        $postRecommend = PostRecommend::firstOrCreate(['user_id' => $user->id]);
        //2.找出指针：最新，随机 每个用户的推荐视频推荐表，就是日刷指针记录表，找到最近未刷完的指针（指针含日期和review_id）
        $reviewId  = Post::getNextReviewId($postRecommend->day_review_ids, $maxReviewIdInDays);
        $reviewDay = substr($reviewId, 0, 8);

        //视频刷光了,先返回20个最新的视频顶一下，有点逻辑需要分析
        if (is_null($reviewId)) {
            return $qb->latest('id')->skip(rand(1, 100))->take(20)->get();
        }

        //3.取未刷完的这天的指针后的视频
        $qb = $qb->take($limit);
        $qb = $qb->where('review_day', $reviewDay)
            ->where('review_id', '>', $reviewId)
            ->orderBy('review_id');

        //获取数据
        $posts = $qb->get();

        // 视频刷光了,先返回20个最新的视频顶一下
        if (!$posts->count()) {
            Log::channel('fast_recommend')->error($reviewId . '指针没空，结果是空' . $reviewDay);
            $qb_published = Post::has('video')->with(['video', 'user', 'user.role'])->publish();
            return $qb_published->latest('id')->skip(rand(1, 100))->take(20)->get();
        }

        //用户和当前这堆视频动态的 喜欢状态（是否已喜欢过，更新post->liked）
        //TODO: 后续换倒排表，到推荐子喜欢单次查询返回结果集
        $posts = Post::likedPosts($user, $posts);

        //关注动态的用户（是否已关注过，更新post->followed)
        //TODO: 后续换倒排表，到推荐子喜欢单次查询返回结果集
        $posts = Post::followedPostsUsers($user, $posts);

        //4.更新指针
        $postRecommend->updateCursor($posts);

        //混合广告视频
        $mixPosts = Post::mixPosts($posts);

        return $mixPosts;
    }

    /**
     * 查询该刷哪天的哪个位置了...
     *
     * @param $userReviewIds 用户刷过的指针记录
     * @param $maxReviewIdInDays 全动态表里所有的每天的最大review_ids
     * @return int|mixed|null
     */
    public static function getNextReviewId($userReviewIds, $maxReviewIdInDays)
    {
        //用户每日刷的 reviewid 指针
        $reviewId      = null;
        $userReviewIds = $userReviewIds ?: [];
        rsort($userReviewIds);
        $userReviewIdsByDay = [];
        //FIXME: UserAttr(userReviewIdsByDay) = 返回用户刷过的每天的指针记录的数组
        foreach ($userReviewIds as $userDayReviewId) {
            $reviewDay = substr($userDayReviewId, 0, 8);
            //生成数组
            $userReviewIdsByDay[$reviewDay] = $userDayReviewId;
        }

        foreach ($maxReviewIdInDays as $item) {
            //当前reviewDay
            $reviewDay = $item->review_day;
            //里最大的review_id
            $maxReviewId = $item->max_review_id;

            //获取用户刷的（当前reviewDay）日指针
            $userDayReviewId = Arr::get($userReviewIdsByDay, $reviewDay);

            //未刷过该日视频
            if (is_null($userDayReviewId)) {

                $reviewId = Post::where('review_day', $reviewDay)->min('review_id') - 1;
                break;
            }

            //未刷完该日视频
            if ($maxReviewId > $userDayReviewId) {

                $reviewId = $userDayReviewId;
                break;
            }

            //刷完了改日的，查询下一天的.. 直到找到review_id
        }

        return $reviewId; //null 表示刷完了全站视频...
    }

    //粘贴时：保存抖音爬虫视频动态
    public static function saveSpiderVideoPost($spider)
    {
        $post = Post::firstOrNew(['spider_id' => $spider->id]);

        //创建动态 避免重复创建..
        if (!isset($post->id)) {
            $post->user_id    = $spider->user_id;
            $post->content    = Arr::get($spider->data, 'title', '');
            $post->status     = Post::PRIVARY_STATUS; //草稿，爬虫抓取中
            $post->created_at = now();
            $post->save();
        }
    }

    //抖音爬虫成功时：发布视频动态
    public static function publishSpiderVideoPost($spider)
    {
        $post           = Post::where(['spider_id' => $spider->id])->first();
        $post->video_id = $spider->spider_id; //爬虫的类型spider_type="videos",这个video_id只有爬虫成功后才有...

        if ($post) {
            Post::publishPost($post);
        }
    }

    /**
     * 发布动态，随机归档，奖励...
     */
    public static function publishPost(Post $post)
    {
        $post->status = Post::PUBLISH_STATUS; //发布成功动态

        // $post->review_id  = Post::makeNewReviewId(); //定时发布时决定，有定时任务处理一定数量或者时间后随机打乱
        // $post->review_day = Post::makeNewReviewDay();
        $post->save();

        //FIXME: 这个逻辑要放到 content 系统里，PostObserver updated ...
        //超过100个动态或者已经有1个小时未归档了，自动发布.
        $canPublished = Post::where('review_day', 0)
            ->where('created_at', '<=', now()->subHour())->exists()
            || Post::where('review_day', 0)->count() >= 100;

        if ($canPublished) {
            dispatch_now(new PublishNewPosts);
        }

        //抖音爬的视频，可直接奖励
        $user = $post->user;
        if (!is_null($user)) {
            //触发奖励
            Gold::makeIncome($user, 10, '分享视频奖励');
            //扣除精力-1
            if ($user->ticket > 0) {
                $user->decrement('ticket');
            }
        }
    }
}
