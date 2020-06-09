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
        return Post::latest('id')->publish()->where('user_id', $args['user_id']);
    }
}
