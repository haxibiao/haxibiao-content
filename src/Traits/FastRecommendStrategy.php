<?php

namespace Haxibiao\Content\Traits;

use App\Post;
use App\PostRecommend;
use App\User;
use App\UserBlock;
use Illuminate\Support\Arr;
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
     * @param mixed $query 基础推荐数据范围查询
     * @param mixed $scope 推荐排重游标范围名称(视频刷，电影剪辑)
     * @return array
     */
    public static function fastRecommendPosts($limit = 4, $query = null, $scope = null)
    {
        $posts = collect([]);
        //登录用户
        $user = getUser();
        //基础推荐数据 - 全部有视频的动态
        if (is_null($query)) {
            $query = Post::has('video')->with(['video', 'user.profile'])->publish();
        }
        $qb = $query->with(['video', 'user.profile'])->publish();

        //开始推荐 - 把每天的最大指针拿进一个数组
        $maxReviewIdInDays = Post::getMaxReviewIdInDays();

        //1.过滤 过滤掉自己 和 不喜欢或拉黑过的用户的作品
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
            //默认不喜欢刷到自己的视频动态? 我看可以，性能更好
            // $notLikIds[] = $user->id;
            // $qb          = $qb->whereNotIn('user_id', $notLikIds);
        }

        $postRecommend = PostRecommend::fetchByScope($user, $scope);
        //2.找出指针：最新，随机 每个用户的推荐视频推荐表，就是日刷指针记录表，找到最近未刷完的指针（指针含日期和review_id）
        $reviewId = Post::getNextReviewId($postRecommend->day_review_ids, $maxReviewIdInDays);
        $reviewDay = substr($reviewId, 0, 8);
        //视频刷光了,随机返回4个
        if (is_null($reviewId)) {
            // 优先编辑的精品
            if (in_array(config('app.name'), ['yinxiangshipin', 'caohan'])) {
                $vestIds = User::whereIn('role_id', [User::VEST_STATUS, User::EDITOR_STATUS])->pluck('id')->toArray();
                $qb      = $qb->whereIn('user_id', $vestIds);
            }
            $result = $qb->latest('id')->skip(rand(1, 100))->take(4)->get();
            return $qb->latest('id')->skip(rand(1, 100))->take(4)->get();
        }
        //3.取未刷完的这天的指针后的视频
        $qb = $qb->take($limit);
        $qb = $qb->where('review_day', $reviewDay)
            ->where('review_id', '>', $reviewId)
            ->orderBy('review_id');

        //获取数据
        $posts = $qb->get();

        if (!request('fast_recommend_mode')) {
            //更新点赞状态
            $posts = Post::likedPosts($user, $posts);
            //更新关注状态
            $posts = Post::followedPostsUsers($user, $posts);
        }

        //4.更新指针
        $postRecommend->updateCursor($posts);

        //混合广告视频
        $mixPosts = $posts;
        if (adIsOpened()) {
            $mixPosts = Post::mixPosts($posts);
        }

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

            //刷完了该日的，查询下一天的.. 直到找到review_id
        }

        return $reviewId; //null 表示刷完了全站视频...
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
        $maxRviewIds = \Haxibiao\Content\Post::selectRaw("review_day,max(review_id) as max_review_id")
            ->whereStatus(1) //只考虑已上架发布的动态
            ->whereNotNull('video_id') //只考虑有视频的
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
}
