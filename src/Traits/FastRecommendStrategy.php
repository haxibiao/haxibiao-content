<?php

namespace Haxibiao\Content\Traits;

use App\Post;
use App\PostRecommend;
use App\User;
use App\UserBlock;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * 快速推荐动态算法策略
 */
trait FastRecommendStrategy
{
    /**
     * 目前最简单的错日排重推荐视频算法(FastRecommend)，人人可以看最新，随机，过滤，不重复的视频流了
     *
     * @param int $limit 返回动态条数
     * @param mixed $query 基础推荐数据范围查询,可带with预查询优化，排序等
     * @param mixed $scope 推荐排重游标范围名称(视频，电影)
     * @param mixed $scopeQuery 匹配scope的范围用来算刷的位置，无排序和with优化
     * @return array
     */
    public static function fastRecommendPosts($limit = 4, $query = null, $scope = null, $scopeQuery = null)
    {
        $posts = collect([]);
        //登录用户
        $user = getUser();
        //0.准备 刷的内容范围加载
        if (is_null($query)) {
            //默认推荐刷 = 纯未分类的动态，不带影视,不带题目，需要的resolver自己传入query
            $query = Post::join('videos', 'posts.video_id', 'videos.id')->whereNotNull('videos.path')->whereNull('videos.movie_id')->whereNull('question_id');
        }
        $qb = (clone $query)->with(['video', 'user', 'audio'])->publish();

        //0.准备 提取刷过的位置记录
        $maxReviewIdInDays = Post::getMaxReviewIdInDays($scopeQuery ?? $query);

        //1.过滤 不喜欢和拉黑过的用户的作品
        if (!request('fast_recommend_mode')) {
            $notLikIds = [];
            if (class_exists("App\Dislike")) {
                $notLikIds = $user->dislikes()->ByType('users')->get()->pluck('dislikeable_id')->toArray();
            }
            if (class_exists('App\UserBlock')) {
                $blockIds = UserBlock::where("user_id", $user->id)
                    ->where('blockable_type', 'users')
                    ->pluck('blockable_id')
                    ->toArray();

                $notLikIds = array_unique(array_filter(array_merge($notLikIds, $blockIds)));
            }
            //默认不喜欢刷到自己的视频动态? 我看可以，少个notin性能更好
        }

        $postRecommend = PostRecommend::fetchByScope($user, $scope);
        //2.找出最后刷到的位置
        $reviewId  = $postRecommend->getNextReviewId($maxReviewIdInDays);
        $reviewDay = substr($reviewId, 0, 8);
        //视频刷光时
        if (is_null($reviewId)) {
            $reviewDay = $postRecommend->resetReviewDayByRandom();
            $reviewId  = $postRecommend->getNextReviewId($maxReviewIdInDays);

            // 最后补刀到推荐视频中
            if (is_null($reviewDay)) {
                // 优先刷编辑用户的精品内容
                if (User::where('role_id', User::EDITOR_STATUS)->exists()) {
                    $vestIds = User::whereIn('role_id', [User::VEST_STATUS, User::EDITOR_STATUS])->pluck('id')->toArray();
                    $qb      = $qb->whereIn('user_id', $vestIds);
                }
                // 最新100个中的4个
                return $qb->latest('id')->skip(rand(1, 100))->take(4)->get();
            }
        }

        //3.从最后刷到的位置取内容
        $qb = $qb->where('review_day', $reviewDay)
            ->where('review_id', '>', $reviewId)
            ->orderBy('review_id');
        $qb = $qb->take($limit);

        //4.获取数据
        $posts = $qb->onlyReadSelf()->get();
        if (!request('fast_recommend_mode')) {
            //更新点赞状态？点过赞的，以后刷不到了，临时状态前端已缓存点赞状态，UI来回点赞状态不消失
            // $posts = Post::likedPosts($user, $posts);
            //更新关注状态? UI都没有
            // $posts = Post::followedPostsUsers($user, $posts);
        }

        //5.保存最后刷的位置
        FastRecommendStrategy::updateCursor($posts, $postRecommend);
        //6.混合广告视频
        $posts = FastRecommendStrategy::mixAdPosts($posts);
        //7.混合教学视频
        $posts = FastRecommendStrategy::mixGuidPosts($posts);
        return $posts;
    }

    /**
     * 混合教学视频
     * @param \Illuminate\Support\Collection $posts
     */
    public static function mixGuidPosts($posts): Collection
    {
        $mixedPosts = [];
        foreach ($posts as $post) {
            $mixedPosts[] = $post;
        }
        //遇到只取到<=1个，加入1个视频避免前端刷不动
        if ($posts->count() == 1) {
            if (adIsOpened()) {
                $post            = $posts->first();
                $adPost          = clone $post;
                $adPost->id      = random_str(7);
                $adPost->is_ad   = true;
                $adPost->ad_type = "tt"; //FIXME: 后面新增 教学视频 type: guid
                $mixedPosts[]    = $adPost;
            }

            // 兼容前端没开启广告 也没录制好教学视频的情况 追加随机推荐的4个
            $qb              = Post::has('video')->with(['video', 'user'])->publish();
            $randLatestPosts = $qb->latest('id')->skip(rand(1, 100))->take(4)->get();
            foreach ($randLatestPosts as $post) {
                $mixedPosts[] = $post;
            }
        }
        return collect($mixedPosts);
    }

    /**
     * 混合广告视频
     * @param \Illuminate\Support\Collection $posts
     */
    public static function mixAdPosts($posts): Collection
    {
        if (!adIsOpened()) {
            return $posts;
        }
        $mixPosts = [];
        if ($posts->count() < 4) {
            //少于4个，不加广告
            return $posts;
        } else {
            $index = 0;
            foreach ($posts as $post) {
                $index++;
                $mixPosts[] = $post;
                if ($index % 4 == 0) {
                    //每隔4个插入一个广告
                    $adPost          = clone $post;
                    $adPost->id      = random_str(7);
                    $adPost->is_ad   = true;
                    $adPost->ad_type = Post::diyAdShow() ?? "tt";
                    $mixPosts[]      = $adPost;
                }
            }
        }
        return collect($mixPosts);
    }

    /**
     * 查询该刷哪天的哪个位置了...
     *
     * @param $userReviewIds 用户刷过的指针记录
     * @param $maxReviewIdInDays 内容范围里所有的每天的最大review_ids
     * @return int|mixed|null
     */
    public static function getNextReviewId($userReviewIds, $maxReviewIdInDays)
    {
        //用户刷到的最近停留位置，null 表示刷完了该范围全部内容...
        $reviewId      = null;
        $userReviewIds = $userReviewIds ?: [];
        //按日期倒排
        rsort($userReviewIds);

        //通过指针算出来用户每天的内容已刷的位置
        $userReviewIdsByDay = [];
        foreach ($userReviewIds as $userDayReviewId) {
            $reviewDay = substr($userDayReviewId, 0, 8);
            //生成数组
            $userReviewIdsByDay[$reviewDay] = $userDayReviewId;
        }

        //逐天查询未刷完日期的最后位置
        foreach ($maxReviewIdInDays as $item) {
            //当前reviewDay
            $reviewDay = $item->review_day;
            //最后位置
            $maxReviewId = $item->max_review_id;

            // 脏数据区间,直接跳过！
            if ($reviewDay <= 0) {
                continue;
            }

            //获取用户刷的（当前reviewDay）日指针
            $userDayReviewId = Arr::get($userReviewIdsByDay, $reviewDay);

            //未刷过该日视频 - 直接返回最小的
            if (is_null($userDayReviewId)) {
                $reviewId = Post::where('review_day', $reviewDay)->min('review_id') - 1;
                break;
            }

            //未刷完该日视频 - 找到位置返回
            if ($maxReviewId > $userDayReviewId) {
                $reviewId = $userDayReviewId;
                break;
            }
        }
        return $reviewId;
    }

    /**
     * 每取一次内容推荐，保存下最后刷的位置(按日期的)
     */
    public static function updateCursor($posts, $postRecommend)
    {
        $reviewIds = $posts->pluck('review_id')->toArray();
        if (!blank($reviewIds)) {
            //拿到最后一次刷提取内容的位置
            $maxReviewId = max($reviewIds);

            //获取review_day
            $maxReviewDay = substr($maxReviewId, 0, 8);

            //获取 记录的所有天的刷的位置
            $dayReviewIds = $postRecommend->day_review_ids ?? [];
            $isExisted    = false;
            foreach ($dayReviewIds as &$dayReviewId) {
                $day = substr($dayReviewId, 0, 8);
                if ($day == $maxReviewDay && $maxReviewId > $dayReviewId) {
                    //更新最后一次刷的位置
                    $dayReviewId = $maxReviewId;
                    $isExisted   = true;
                }
            }
            if (!$isExisted) {
                //增加你在这一天的最后刷的位置
                $dayReviewIds[] = $maxReviewId;
            }

            //更新 记录的所有天的刷的位置
            $postRecommend->day_review_ids = array_unique($dayReviewIds);
            $postRecommend->save();
        }
    }

    /**
     * 归档并随机化今日新动态
     */
    public static function archiveTodayPosts()
    {
        $reviewDay = Post::genReviewDay();
        Post::where('review_day', 0)->chunk(100, function ($posts) use ($reviewDay) {
            //找到今日最大的review_id
            $maxPostReviewId = Post::where('review_day', $reviewDay)->max('review_id');
            if (is_null($maxPostReviewId)) {
                //默认开始用今日最小归档id
                $maxPostReviewId = FastRecommendStrategy::makeTodayMinReviewId();
            };
            //批量生成一堆新的随机review_id(比最新max的都大)
            $reviewIds = FastRecommendStrategy::makeReviewIds($maxPostReviewId, count($posts));
            foreach ($posts as $index => $post) {
                //统一下架更新review_id,避免用户刷到高位id,导致错过部分视频(当这个小时内批量更新的数量多时，有可能)
                $post->review_id  = $reviewIds[$index];
                $post->review_day = Post::genReviewDay();
                $post->status     = Post::PRIVARY_STATUS; //先不上架，避免被刷到了....
                $post->save();
            }
            //再重新上架回去(一次上架100个，避免刷的指针飘了)
            Post::whereIn('id', $posts->pluck('id'))->update(['status' => Post::PUBLISH_STATUS]);
        });
    }

    /**
     * 按日期生成review_id - 修复归档用
     */
    public static function makeNewReviewId($reviewDay = null, $capacity = 100, $maxId = null)
    {
        //随机范围10w,如果一天内新增内容数量超过10w，需要增加这个数值...
        $maxNum    = 100000;
        $reviewDay = is_null($reviewDay) ? today()->format('Ymd') : $reviewDay;
        $reviewId  = intval($reviewDay) * $maxNum + mt_rand(1, $maxNum - 1);
        //TODO: 如何避开新生成的这个review_id 今天已经生成过了，找个空缺的位置填充
        return $reviewId;
    }

    /**
     * 旧数据补充修复 review_id 归档 - 修复数据用
     */
    public function reviewId(Post $post = null)
    {
        $post = $post ?? $this;
        if ($post) {
            $reviewId = $post->review_id;
            if (is_null($reviewId)) {
                $reviewDay = is_null($post->created_at) ? null : $post->created_at->format('Ymd');
                $reviewId  = FastRecommendStrategy::makeNewReviewId($reviewDay);
            }
            return $reviewId;
        }
        return null;
    }

    /**
     * 是否今日归档id
     */
    public static function isTodayReviewId($reviewId)
    {
        $prefix = today()->format('Ymd') . '*';
        return Str::is($prefix, $reviewId);
    }

    /**
     * 获取今日最小归档id
     */
    public static function todayMinReviewId()
    {
        $minReviewPost = Post::select('review_id')
            ->where('review_id', '>=', today()->format('Ymd') * 100000 + 1)
            ->orderBy('review_id')
            ->first();
        $reviewId = data_get($minReviewPost, 'review_id', 0);
        return $reviewId;
    }

    /**
     * 范围允许的今日最大归档id
     */
    public static function makeTodayMaxReviewId()
    {
        $reviewDay = Post::genReviewDay();
        $maxNum    = Post::TODAY_MAX_POST_NUM - 1;

        return ($reviewDay * $maxNum) + $maxNum;
    }

    /**
     * 范围允许的今日最小归档id
     */
    public static function makeTodayMinReviewId()
    {
        $reviewDay = Post::genReviewDay();
        $maxNum    = Post::TODAY_MAX_POST_NUM;

        return ($reviewDay * $maxNum) + 1;
    }

    /**
     * 每天最大归档id结果分布
     *
     * @return Collection
     */
    public static function getMaxReviewIdInDays($scopeQuery = null)
    {
        $scopeQuery = $scopeQuery ?? \Haxibiao\Content\Post::query();
        // 历史的review_day g跟 max_review_id 极少会更新,所以cache住历史查询结果
        $baseQuery = clone $scopeQuery->selectRaw("review_day,max(review_id) as max_review_id")
            ->where('posts.status', 1) //只考虑已上架发布的动态
            ->whereNotNull('video_id') //只考虑有视频的
            ->groupBy('review_day')
            ->latest('review_day');
        // FIXME::每日新增视频很少的时候取不到数据
        // 23:59:59秒时刻就过期
        // $ttl = today()->addDay()->diffInSeconds(now()) - 1;
        // $historyMaxReviewIds = Cache::remember('history:reviewids', $ttl, function () use ($baseQuery) {
        //     return clone $baseQuery->where('review_day', '<', date('Ymd'))->get();
        // });

        $historyMaxReviewIds = clone $baseQuery->where('review_day', '<', date('Ymd'))->get();

        $todaymaxRviewIds = clone $baseQuery->where('review_day', date('Ymd'))->get();
        $maxRviewIds      = $historyMaxReviewIds->concat($todaymaxRviewIds);
        return $maxRviewIds;
    }

    /**
     * 格式化归档日期字符串
     */
    public static function genReviewDay($date = null)
    {
        $date = $date ?? today();
        return $date->format('Ymd');
    }

    /**
     * 给每小时批量生成review_ids用
     */
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
}