<?php

namespace haxibiao\content\Traits;

use haxibiao\content\Post;

trait PostResolvers
{
    public function resolveRecommendPosts($root, $args, $context, $info)
    {
        app_track_user_event("获取学习视频");
        return Post::getRecommendPosts();
    }

    public function resolvePosts($root, $args, $context, $info)
    {
        app_track_user_event("个人主页视频动态");
        $qb = Post::latest('id');
        //自己看自己的发布列表时，需要看到未成功的爬虫视频动态...
        if (getUserId() == $args['user_id']) {
            $qb = $qb->publish();
        }
        return $qb->where('user_id', $args['user_id']);
    }
}
