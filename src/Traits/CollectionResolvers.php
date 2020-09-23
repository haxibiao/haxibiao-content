<?php

namespace Haxibiao\Content\Traits;

use App\Collection;
use App\Image;
use App\Post;
use App\User;
use GraphQL\Type\Definition\ResolveInfo;
use Haxibiao\Base\Exceptions\GQLException;
use Illuminate\Support\Arr;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

trait CollectionResolvers
{

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
        $type = data_get($args, 'type');
        $description = data_get($args, 'description', '');
        $post_ids = data_get($args, 'post_ids');

        if ($logo) {
            $image = Image::saveImage($logo);
            $logo = $image->path;
        } else {
            $logo = User::AVATAR_DEFAULT;
        }

        $collection = static::firstOrCreate([
            'user_id' => getUser()->id,
            'name' => $name,
        ],[
            'description' => $description,
            'logo' => $logo,
            'type' => $type,
            'status' => Collection::STATUS_ONLINE
        ]);
        // TODO 限制只能只能是POST（待修）
        if ($post_ids) {
            $collection->collect($post_ids,\App\Post::class);
        }
        return $collection;
    }

    public function resolveCollection($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $collection_id = Arr::get($args, 'collection_id');
        app_track_event('合集玩法', '查看合集内视频', $collection_id);
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
        ]);
        return $collection;
    }

    /**
     * 添加资源对象至合集
     */
    public function resolveMoveInCollection($rootValue, array $args, $context, $resolveInfo)
    {
        $collection_ids = data_get($args, 'collection_ids');
        $post_ids = Arr::get($args, 'post_ids');
        foreach ($post_ids as $post_id) {
            $post = Post::find($post_id);
            if ($post) {
                $post->collectivize($collection_ids);
            }
        }
        return true;
    }

    /**
     * 移除合集中的资源对象
     */
    public function resolveMoveOutCollection($rootValue, array $args, $context, $resolveInfo)
    {
        $collection_ids = Arr::get($args, 'collection_ids');
        $post_ids = Arr::get($args, 'post_ids');

        foreach ($collection_ids as $collection_id) {
            $collection = static::find($collection_id);
            if ($collection) {
                $collection->uncollect($post_ids,\App\Post::class);
            }
        }
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

        $currentEpisode =  $perPage * ($currentPage - 1 ) + 1;
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
}
