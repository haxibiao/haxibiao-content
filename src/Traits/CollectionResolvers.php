<?php

namespace Haxibiao\Content\Traits;

use App\Collection;
use App\Image;
use App\Visit;
use GraphQL\Type\Definition\ResolveInfo;
use Haxibiao\Breeze\Exceptions\GQLException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

trait CollectionResolvers
{
    public function resolveCollections($rootValue, array $args, $context, $resolveInfo)
    {
        $collections = Collection::where('user_id', data_get($args, 'user_id'))->where('name', 'like', '%' . ($args['keyword'] ?? '') . '%')
            ->orderByDesc('updated_at');
        app_track_event('用户页', '我的合集', '用户:' . data_get($args, 'user_id'));
        return $collections;
    }

    //分享合集url
    public function getShareLink($rootValue, array $args, $context, $resolveInfo)
    {
        $collection = Collection::with('posts')->find($args['collection_id']);
        throw_if(is_null($collection), GQLException::class, '该合集不存在哦~,请稍后再试');

        $shareMag = config('haxibiao-content.share_config.share_collection_msg', '#%s/share/post/%d#, #%s#,打开【%s】,直接观看合集视频,玩视频就能赚钱~,');

        if (currentUser() && class_exists("App\\Helpers\\Redis\\RedisSharedCounter", true)) {
            $user = getUser();
            \App\Helpers\Redis\RedisSharedCounter::updateCounter($user->id);
            //触发分享任务
            $user->reviewTasksByClass('Share');
        }

        return sprintf($shareMag, config('app.url'), $collection->id, $collection->description, config('app.name_cn'));
    }

    /**
     * 创建合集
     */
    public function resolveCreateCollection($rootValue, array $args, $context, $resolveInfo)
    {
        $name            = data_get($args, 'name');
        $logo            = data_get($args, 'logo');
        $collectableType = data_get($args, 'collectable_type');
        $description     = data_get($args, 'description', '');
        $collectableIds  = data_get($args, 'collectable_ids');

        if ($logo) {
            $image = Image::saveImage($logo);
            $logo  = $image->path;
        } else {
            $logo = config('haxibiao-content.collection_default_logo');
        }

        $collection = Collection::firstOrCreate([
            'user_id' => getUserId(),
            'name'    => $name,
        ], [
            'description' => $description,
            'logo'        => $logo,
            'type'        => $collectableType,
            'status'      => Collection::STATUS_UNSIGN,
        ]);
        if ($collectableIds) {
            $collection->collect($collectableIds, $collectableType);
        }
        app_track_event('合集玩法', '创建合集', '合集名:' . data_get($args, 'name'));
        return $collection;
    }

    public function resolveCollection($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        //标记获取详情数据信息模式
        request()->request->add(['fetch_sns_detail' => true]);
        $collection_id = Arr::get($args, 'collection_id');
        app_track_event('合集玩法', '查看合集内视频', '合集id:' . $collection_id);
        if (currentUser()) {
            //添加集合浏览记录
            $user = getUser();
            Visit::createVisit($user->id, $collection_id, 'collections');
        }

        return Collection::findOrFail($collection_id);
    }

    /**
     * 修改合集
     */
    public function resolveUpdateCollection($rootValue, array $args, $context, $resolveInfo)
    {
        $collection_id = data_get($args, 'collection_id');
        $collection    = Collection::findOrFail($collection_id);

        $logo = Arr::get($args, 'logo');
        if ($logo) {
            $image = Image::saveImage($logo);
            $logo  = $image->path;
        } else {
            $logo = $collection->logo;
        }
        $collection->update([
            'logo'        => $logo,
            'name'        => Arr::get($args, 'name', $collection->name),
            'type'        => Arr::get($args, 'type', $collection->type),
            'description' => Arr::get($args, 'description', $collection->description),
        ]);
        app_track_event('合集玩法', '修改合集', '合集id:' . $collection_id);
        return $collection;
    }

    /**
     * 添加资源对象至合集
     */
    public function resolveMoveInCollection($rootValue, array $args, $context, $resolveInfo)
    {
        $collectionId    = data_get($args, 'collection_id');
        $collectableIds  = data_get($args, 'collectable_ids');
        $collectableType = data_get($args, 'collectable_type');

        $collection = Collection::find($collectionId);
        if (!$collection) {
            return false;
        }

        $collection->recollect($collectableIds, $collectableType);
        app_track_event('合集玩法', '添加资源对象至合集');
        return true;
    }

    /**
     * 移除合集中的资源对象
     */
    public function resolveMoveOutCollection($rootValue, array $args, $context, $resolveInfo)
    {
        $collectionId    = data_get($args, 'collection_id');
        $collectableIds  = data_get($args, 'collectable_ids');
        $collectableType = data_get($args, 'collectable_type');

        $collection = Collection::find($collectionId);
        if (!$collection) {
            return false;
        }

        $collection->uncollect($collectableIds, $collectableType);
        app_track_event('合集玩法', '移除合集中的资源对象');
        return true;
    }

    /**
     * 查询合集下的资源对象列表
     */
    public function resolvePosts($rootValue, $args, $context, $resolveInfo)
    {

        $order       = data_get($args, 'order');
        $currentPage = data_get($args, 'page');
        $perPage     = data_get($args, 'count');

        $qb    = $rootValue->posts()->publish();
        $total = $qb->count();

        if (in_array(
            ['video', 'collections', 'images'],
            data_get($resolveInfo->getFieldSelection(1), 'data')
        )) {
            $qb->with(['video', 'collections', 'images']);
        }

        $postList = $qb->when($order == 'LATEST', function ($q) {
            $q->orderBy('sort_rank');
        })->skip(($currentPage * $perPage) - $perPage)
            ->take($perPage)
            ->get();

        $currentEpisode = $perPage * ($currentPage - 1) + 1;
        foreach ($postList as $post) {
            $post->current_episode = $currentEpisode;
            $currentEpisode++;
        }

        return new \Illuminate\Pagination\LengthAwarePaginator($postList, $total, $perPage, $currentPage);
    }

    /**
     * 搜索合集
     */
    public function resolveSearchCollections($rootValue, $args, $context, $resolveInfo)
    {
        return Collection::search(data_get($args, 'query'));
    }

    /**
     * 搜索合集
     */
    public function resolveTypeCollections($rootValue, $args, $context, $resolveInfo)
    {
        $qb = Collection::where('status', Collection::STATUS_UNSIGN)->where('type', $args['type']);
        if ($args['user_id'] ?? null) {
            $qb->where('user_id', $args['user_id']);
        }
        return $qb;
    }
    /**
     * 随机推荐的一组集合
     */
    public function resolveRandomCollections($rootValue, $args, $context, $resolveInfo)
    {
        $currentPage = data_get($args, 'page');
        $perPage     = data_get($args, 'count');

        //过滤掉推荐列表中的集合
        $qb = Collection::where(function ($query) {
            $query->whereNull('sort_rank')
                ->orWhere('sort_rank', 0);});
        // $qb = Collection::whereNull('sort_rank')->orWhere('sort_rank', 0);

        //登录用户
        if (currentUser()) {
            $user = getUser(false);
            //过滤掉自己 和 不喜欢用户的作品
            if (class_exists("App\Dislike")) {
                $notLikIds   = $user->dislikes()->ByType('users')->get()->pluck('dislikeable_id')->toArray();
                $notLikIds[] = $user->id;
                $qb          = $qb->whereNotIn('user_id', $notLikIds);
            }
        }
        //动态数量大于三的
        $qb = $qb->where('count_posts', '>=', 3);
        //过滤掉合集封面为默认封面的
        $qb = $qb->whereNotNull('logo')
            ->where('logo', '!=', config('haxibiao-content.collection_default_logo'));

        //按照合集创建时间排序
        $qb = $qb->latest();

        $total = $qb->count();

        // FIXME:跳过十条数据的逻辑，在之前数据不充足的前提下会出现 count 预期数量与实际返回不一致
        $array = $qb
            ->skip(($currentPage * $perPage) - $perPage)
            ->take($perPage)
            ->orderBy('created_at', 'desc')
            ->get();

        $collections = new \Illuminate\Pagination\LengthAwarePaginator(
            $array->shuffle(),
            $total,
            $perPage,
            $currentPage
        );
        return $collections;
    }
    /**
     * 推荐集合列表
     */

    public function resolveRecommendCollections($rootValue, $args, $context, $resolveInfo)
    {
        if (in_array(config('app.name'), ['dianyintujie'])) {
            $qb = Collection::where('sort_rank', '>=', Collection::RECOMMEND_COLLECTION)
                ->orderby('sort_rank', 'asc');
            $recommendCollectionsA = $qb->take(6)->skip(0)->get();
            $recommendCollectionsB = $qb->take(6)->skip(6)->get();
            //降低rank值，减少出现的概率
            Collection::whereIn('id', $recommendCollectionsA->pluck('id'))
                ->whereIn('id', $recommendCollectionsB->pluck('id'))
                ->increment('sort_rank');
            $result['recommendCollectionsA'] = $recommendCollectionsA;
            $result['recommendCollectionsB'] = $recommendCollectionsB;
            return $result;
        }
        //置顶的合集
        $topCollection = Collection::top()->first();

        $qb = Collection::where('sort_rank', '>=', Collection::RECOMMEND_COLLECTION)
            ->orderby('updated_at', 'desc');
        $recommendCollectionsA = $qb->take(3)->skip(0)->get();
        $recommendCollectionsB = $qb->take(3)->skip(3)->get();

        $result = [];

        if(!$topCollection){
            $topCover = null;
        }else{
            $topCover = $topCollection->logo;
        }

        //构建返回结果
        $result['topCover']              = Collection::getTopCover() ?? $topCover;
        $result['topCollection']         = $topCollection;
        $result['recommendCollectionsA'] = $recommendCollectionsA;
        $result['recommendCollectionsB'] = $recommendCollectionsB;

        return $result;
    }
}
