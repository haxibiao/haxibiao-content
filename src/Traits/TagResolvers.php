<?php

namespace Haxibiao\Content\Traits;

use Haxibiao\Content\Tag;

trait TagResolvers
{
    /**
     * 标签下的动态列表
     */
    public function resolvePosts($rootValue, $args, $context, $resolveInfo)
    {

        $visibility = data_get($args, 'visibility');
        $order      = data_get($args, 'order');
        $user       = getUser(false);

        $qb = $rootValue->posts()->publish();

        $qb->when($visibility == 'self', function ($q) use ($user) {
            $q->where('taggables.user_id', data_get($user, 'id'));
        });

        $qb->when($order == 'LATEST', function ($q) {
            $q->orderByDesc('id');
        });

        return $qb;
    }

    public function resolveSearchTags($rootValue, $args, $context, $resolveInfo)
    {
        return Tag::search(data_get($args, 'query'));
    }

}
