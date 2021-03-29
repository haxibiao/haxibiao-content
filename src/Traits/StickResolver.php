<?php

namespace Haxibiao\Content\Traits;

use App\EditorChoice;
use App\Stick;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

trait StickResolver
{
    public function resolveTodayRecommend()
    {
        $editor = EditorChoice::where('title', '今日推荐')->first();
        // 数量不多，in random order 解决每次返回的数据不同
        if ($editor) {
            return Stick::where('editor_choice_id', $editor->id)->inRandomOrder()->take(4)->get();
        } else {
            return Stick::where('stickable_type', 'movies')->inRandomOrder()->take(4)->get();
        }
    }

    public function resolveStickyList($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $place          = data_get($args, 'place', 'index');
        $stickable_type = data_get($args, 'type', 'movies');
        $appName        = data_get($args, 'app');
        $siteId         = data_get($args, 'site_id)');
        $count          = data_get($args, 'count', 4);

        //筛选出对应APP或者网站下的
        $sticky = Stick::where('place', $place)
            ->where('stickable_type', $stickable_type)
            ->where(function ($query) use ($appName) {
                if (isset($appName)) {
                    return $query->where('app_name', $appName);
                } else {
                    return $query->whereNull('app_name');
                }
            })
            ->where(function ($query) use ($siteId) {
                if (isset($siteId)) {
                    return $query->where('site_id', $siteId);
                } else {
                    return $query->whereNull('site_id');
                }
            })
            ->inRandomOrder()
            ->take($count)
            ->get();
        return $sticky;

    }
}
