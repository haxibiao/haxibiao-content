<?php

namespace Haxibiao\Content\Traits;

use GraphQL\Type\Definition\ResolveInfo;
use Haxibiao\Content\Category;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

trait CategoryResolvers
{
    // resolvers
    public function resolveAdmins($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $category = self::findOrFail($args['category_id']);
        return $category->admins();
    }

    public function resolveAuthors($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $category = self::findOrFail($args['category_id']);
        return $category->authors();
    }

    public function resolveFilteredCategories($root, $args, $context, $info)
    {
        $filter = $args['filter'] ?? 'hot';
        //TODO 紧急兼容其它站点老数据问题
        //$qb     = \App\Category::whereIn('type', ['video','article']); //视频时代，避开图文老分类
        $qb = Category::whereStatus(Category::STATUS_PUBLISH); //需上架

        //确保是近1个月内更新过的专题（旧的老分类适合图文时代，可能很久没人更新内容进入了）
        // $qb = $qb->where('updated_at', '>', now()->addMonth(-1));

        //热门话题
        if ($filter == 'hot') {
            $qb = $qb->orderBy('is_official', 'desc');
        } else {
            //最新话题
            $qb = $qb->orderBy('id', 'desc');
        }

        return $qb;
    }

    /**
     * =======================================
     * 下面是question包的resolve
     * =======================================
     */
    //根据类型获取category
    public function resolveCategoriesType($root, array $args, $context, $info)
    {
        return Category::published()->where('type', $args['type']);
    }

    //题库列表
    public function resolveCategories($root, $args, $context, $info)
    {
        app_track_event('首页', '题库列表');
        $keyword = data_get($args, 'key_word');

        //只搜索显示普通题目分类
        $qb = Category::published()
            ->where('type', Category::QUESTION_TYPE_ENUM)
            ->latest('rank');

        if (!empty($keyword)) {
            app_track_event('首页', '搜索题库记录', $keyword);

            $qb->where(function ($query) use ($keyword) {
                $query->where('name', 'like', "%{$keyword}%")
                    ->OrWhere('description', 'like', "%{$keyword}%");
            });
        }
        return $qb;
    }

    //可出题的题库（支持搜索）
    public function resolveCategoriesCanSubmit($root, $args, $context, $info)
    {
        app_track_event('首页', '可出题题库列表');
        $keyword = $args['keyword'] ?? null;
        $user    = getUser();
        return Category::allowUserSubmitQuestions($user->id)->search($keyword);
    }

    //可审题的题库
    public function resolveCategoriesCanAudit($root, $args, $context, $info)
    {
        app_track_event('首页', '可审题题库列表');
        $user = getUser();
        return Category::allowUserAuditQuestions($user->id);
    }

    //获取用户行为数据中最近浏览的五个分类
    public function resolveLatestCategories($root, $args, $context, $info)
    {
        if ($user = currentUser()) {
            if ($action = $user->action) {
                return $action->getLatestCategories($args['top'] ?? 5);
            }
        }
        return [];
    }

    //首页题库列表
    public function resolveSearchCategories($root, $args, $context, $info)
    {
        $keyword = $args['keyword'];
        app_track_event('首页', '搜索题库');
        return Category::searchCategories(getUser(), $keyword);
    }

    public function resolveGuestUserLike($root, $args, $context, $info)
    {
        return Category::guestUserLike($args['offset'], $args['limit']);
    }

    public function resolveNewestCategories($root, $args, $context, $info)
    {
        return Category::newestCategories($args['offset'], $args['limit']);
    }

    public function resolveRecommendCategories($root, $args, $context, $info)
    {
        return Category::recommendCategories($args['offset'], $args['limit']);
    }

    //工厂用查询题库
    public function getByType($rootValue, array $args, $context, $resolveInfo)
    {
        $category = self::where("type", $args['type'])->where("status", 1)->orderBy("order", "desc");
        return $category;
    }
}
