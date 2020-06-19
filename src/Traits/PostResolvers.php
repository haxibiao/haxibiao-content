<?php

namespace haxibiao\content\Traits;

use haxibiao\content\Post;

trait PostResolvers
{
    public function resolveRecommendPosts($root, $args, $context, $info)
    {
        app_track_event("首页", "获取学习视频");
        return Post::getRecommendPosts();
    }

    public function resolvePosts($root, $args, $context, $info)
    {
        app_track_event("用户页", "视频动态");

        return Post::posts($args['user_id']);
    }

    /**
     * 动态广场
     */
    public function resolvePublicPosts($root, $args, $context, $info)
    {
        app_track_event("首页", "访问动态广场");
        return Post::PublicPosts($args['user_id'] ?? null);
    }

    /**
     * 分享视频
     */
    public function getShareLink($rootValue, array $args, $context, $resolveInfo)
    {
        app_track_user('分享', '分享视频');
        return Post::shareLink($args['id']);

        $qb = Post::latest('id');
        //自己看自己的发布列表时，需要看到未成功的爬虫视频动态...
        if (getUserId() == $args['user_id']) {
            $qb = $qb->publish();
        }
        return $qb->where('user_id', $args['user_id']);
    }
}
