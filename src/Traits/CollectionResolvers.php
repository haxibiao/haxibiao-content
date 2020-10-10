<?php

namespace Haxibiao\Content\Traits;

use App\Collection;
use App\Image;
use App\Post;
use App\User;
use App\Visit;
use GraphQL\Type\Definition\ResolveInfo;
use Haxibiao\Base\Exceptions\GQLException;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

trait CollectionResolvers
{
    public function resolveCollections($rootValue, array $args, $context, $resolveInfo){
        return static::where('user_id',data_get($args,'user_id'))
            ->orderByDesc('updated_at');
    }

    //分享合集url
    public function getShareLink($rootValue, array $args, $context, $resolveInfo)
    {
        $collection = static::has('posts')->find($args['collection_id']);
        throw_if(is_null($collection), GQLException::class, '该合集不存在哦~,请稍后再试');

        $shareMag = config('haxibiao-content.share_config.share_collection_msg', '#%s/share/post/%d#, #%s#,打开【%s】,直接观看合集视频,玩视频就能赚钱~,');

        if (checkUser() && class_exists("App\\Helpers\\Redis\\RedisSharedCounter", true)) {
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
        $name = data_get($args, 'name');
        $logo = data_get($args, 'logo');
        $collectableType = data_get($args, 'collectable_type');
        $description = data_get($args, 'description', '');
        $collectableIds    = data_get($args, 'collectable_ids');

        if ($logo) {
            $image = Image::saveImage($logo);
            $logo = $image->path;
        } else {
            $logo = config('haxibiao-content.collection_default_logo');
        }

        $collection = static::firstOrCreate([
            'user_id' => getUser()->id,
            'name' => $name,
        ],[
            'description' => $description,
            'logo' => $logo,
            'type' => $collectableType,
            'status' => Collection::STATUS_ONLINE
        ]);
        if ($collectableIds) {
            $collection->collect($collectableIds,$collectableType);
        }
        return $collection;
    }

    public function resolveCollection($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $collection_id = Arr::get($args, 'collection_id');
        app_track_event('合集玩法', '查看合集内视频', $collection_id);
        if (checkUser()){
            //添加集合浏览记录
            $user = getUser();
            Visit::createVisit($user->id,$collection_id,'collections');
            $user->reviewTasksByClass('Visit');

        }
        return static::findOrFail($collection_id);
    }

    /**
     * 修改合集
     */
    public function resolveUpdateCollection($rootValue, array $args, $context, $resolveInfo)
    {
        $collection_id = data_get($args, 'collection_id');
        $collection = static::findOrFail($collection_id);

        $logo = Arr::get($args, 'logo');
        if ($logo) {
            $image = Image::saveImage($logo);
            $logo = $image->path;
        } else {
            $logo = $collection->logo;
        }
        $collection->update([
            'logo' => $logo,
            'name' => Arr::get($args, 'name', $collection->name),
            'type' => Arr::get($args, 'type', $collection->type),
            'description' => Arr::get($args, 'description', $collection->description),
        ]);
        return $collection;
    }

    /**
     * 添加资源对象至合集
     */
    public function resolveMoveInCollection($rootValue, array $args, $context, $resolveInfo)
    {
        $collectionId = data_get($args, 'collection_id');
        $collectableIds = data_get($args, 'collectable_ids');
        $collectableType = data_get($args, 'collectable_type');

        $collection = static::find($collectionId);
        if(!$collection){
            return false;
        }

        $collection->recollect($collectableIds,$collectableType);
        return true;
    }

    /**
     * 移除合集中的资源对象
     */
    public function resolveMoveOutCollection($rootValue, array $args, $context, $resolveInfo)
    {
        $collectionId = data_get($args, 'collection_id');
        $collectableIds = data_get($args, 'collectable_ids');
        $collectableType = data_get($args, 'collectable_type');

        $collection = static::find($collectionId);
        if(!$collection){
            return false;
        }

        $collection->uncollect($collectableIds,$collectableType);
        return true;
    }

    /**
     * 查询合集下的资源对象列表
     */
    public function resolverPosts($rootValue, $args, $context, $resolveInfo)
    {

        $order       = data_get($args, 'order');
        $currentPage = data_get($args, 'page');
        $perPage     = data_get($args, 'count');

        $qb = $rootValue->posts()->publish();
        $total = $qb->count();

        $postList = $qb->when($order == 'LATEST', function ($q) {
            $q->orderBy('sort_rank');
        })->skip(($currentPage * $perPage) - $perPage)
            ->take($perPage)
            ->get();

        $currentEpisode =  $perPage * ($currentPage - 1) + 1;
        foreach ($postList as $post){
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
        return static::search(data_get($args, 'query'));
    }

    /**
     * 随机推荐的一组集合
     */
    public function resolveRandomCollections($rootValue, $args, $context, $resolveInfo)
    {
        //过滤掉推荐列表中的集合
        $qb=Collection::whereNotNull('sort_rank');

        //登录用户
        if (checkUser()) {
            $user=getUser(false);
            //过滤掉自己 和 不喜欢用户的作品
            $notLikIds   = $user->notLikes()->ByType('users')->get()->pluck('not_likable_id')->toArray();
            $notLikIds[] = $user->id;
            $qb          = $qb->whereNotIn('user_id', $notLikIds);

            //排除浏览过的视频
            $visitVideoIds = Visit::ofType('collections')->ofUserId($user->id)->get()->pluck('visited_id');
            if (!is_null($visitVideoIds)) {
                $qb = $qb->whereNotIn('id', $visitVideoIds);
            }
        } 
        //随机进行排序 最近七天的
        $qb=$qb->inRandomOrder()            
        ->whereBetWeen('created_at', [today()->subDay(7), today()])
        ;

        return $qb;
    }
}
