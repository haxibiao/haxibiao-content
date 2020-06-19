<?php

namespace haxibiao\content\Traits;

use App\Favorite;
use App\Follow;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

trait FavoriteResolvers
{
    public function getByType($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        app_track_event('个人中心', '收藏列表');
        return Follow::where('faved_type', $args['faved_type']);
    }

    public function resolveToggleFavorite($rootValue, array $args, $context, $resolveInfo)
    {
        //兼容老版本接口不传type
        return Favorite::toggleFavorite($args['id'], $args['type'] ?? 'articles');
    }
}
