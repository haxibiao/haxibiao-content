<?php

namespace Haxibiao\Content\Traits;

use App\Follow;
use App\Video;
use Haxibiao\Content\Post;
use Illuminate\Support\Arr;

trait PostResolvers
{
    public function resolvePostByVid($rootValue, array $args, $context, $resolveInfo){
        $videoIds = Video::where('vid',data_get($args,'vid'))->get()
            ->pluck('id')
            ->toArray();
        // TODO 暂时只返回一个
        $post = \App\Post::whereIn('video_id',$videoIds)->first();
        return $post;
    }

    public function resolveRecommendPosts($root, $args, $context, $info)
    {
        app_track_event("首页", "获取学习视频");
        return static::getRecommendPosts();
    }

    public function resolvePosts($root, $args, $context, $info)
    {
        app_track_event("用户页", "视频动态");

        return static::posts($args['user_id'], data_get($args,'keyword'));
    }

    /**
     * 动态广场
     */
    public function resolvePublicPosts($root, $args, $context, $info)
    {
        app_track_event("首页", "访问动态广场");
        return static::publicPosts($args['user_id'] ?? null);
    }

    /**
     * 分享视频
     */
    public function getShareLink($rootValue, array $args, $context, $resolveInfo)
    {
        app_track_event('分享', '分享视频');
        return static::shareLink($args['id']);

        $qb = static::latest('id');
        //自己看自己的发布列表时，需要看到未成功的爬虫视频动态...
        if (getUserId() == $args['user_id']) {
            $qb = $qb->publish();
        }
        return $qb->where('user_id', $args['user_id']);
    }

    /**
     * 获取标签下的视频
     *
     * note:安保联盟在使用它
     * @param $rootValue
     * @param array $args
     * @param $context
     * @param $resolveInfo
     * @return mixed
     */
    public function resolvePostsByTag($rootValue, array $args, $context, $resolveInfo)
    {
        //视频类型
        $type = Arr::get($args, 'type');

        //返回的条数
        $limit = Arr::get($args, 'limit', 5);

        //是否第一次调用接口
        $is_first = Arr::get($args, 'is_first', false);

        $result = static::where('tag_id', $type)
            ->whereStatus(Post::PUBLISH_STATUS)
            ->inRandomOrder()
            ->take($limit)
            ->get();

        //第一次获取学习视频，设置第一条视频为固定视频
        if (Post::STUDY == $type && $is_first) {
            $firstPosts = static::where('tag_id', Post::FIRST)
                ->whereStatus(Post::PUBLISH_STATUS)
                ->get();

            return collect([$firstPosts, $result])->collapse();
        }

        //插入广告
        $adVideo = $result[0];
        $adVideo->is_ad = true;
        $result[] = $adVideo;

        return $result;
    }

    /**
     * 关注用户发布的视频
     *
     * note:安保联盟在使用它
     * @param $rootValue
     * @param array $args
     * @param $context
     * @param $resolveInfo
     * @return array|\Illuminate\Database\Eloquent\Builder
     */
    public function resolveFollowing($rootValue, array $args, $context, $resolveInfo)
    {
        //1.前置准备
        $loginUser = getUser();

        //关注类型
        $filter = 'users';

        //2.获取用户关注列表
        $followedUserIds = Follow::follows($loginUser, $filter)->pluck('followed_id');

        //3.获取关注用户发布的视频
        return static::query()
            ->whereIn('user_id', $followedUserIds)
            ->orderByDesc('created_at');
    }
}
