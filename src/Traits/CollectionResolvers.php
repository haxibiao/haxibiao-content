<?php

namespace Haxibiao\Content\Traits;

use App\Collection;
use App\Image;
use App\Post;
use App\User;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Arr;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

trait CollectionResolvers
{

    //分享合集url
    public function getShareLink($rootValue, array $args, $context, $resolveInfo)
    {
        $collection = Collection::has('posts')->find($args['collection_id']);
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

    // 创建合集信息
    public function resolveCreateCollection($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $name = Arr::get($args, 'name');
        $logo = Arr::get($args, 'logo');
        $type = Arr::get($args, 'type');
        $description = Arr::get($args, 'description', '');
        $post_ids = Arr::get($args, 'post_ids');

        if ($logo) {
            $image = Image::saveImage($logo);
            $logo = $image->path;
        } else {
            $logo = User::AVATAR_DEFAULT;
        }

        $collection = Collection::firstOrNew([
            'user_id' => getUser()->id,
            'name' => $name,
            'description' => $description,
            'logo' => $logo,
            'type' => $type,
            'status' => 1
        ]);
        $collection->save();
        if ($post_ids) {
            $collection->collectByPostIds($post_ids);
        }
        return $collection;
    }

    public function resolveCollection($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $collection_id = Arr::get($args, 'collection_id');
        app_track_event('合集玩法', '查看合集内视频', $collection_id);
        return Collection::findOrFail($collection_id);
    }

    // 创建合集信息
    public function resolveUpdateCollection($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $collection_id = Arr::get($args, 'collection_id');
        $collection = Collection::findOrFail($collection_id);
        $collection->update([
            'name' => Arr::get($args, 'name', $collection->name),
            'type' => Arr::get($args, 'type', $collection->type),
        ]);
        $logo = Arr::get($args, 'logo');
        if ($logo) {
            $image = Image::saveImage($logo);
            $logo = $image->path;
        } else {
            $logo = $collection->logo;
        }
        $collection->update([
            'logo' => $logo,
        ]);
        return $collection;
    }

    // 添加动态到合集中
    public function resolveMoveInCollection($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $collection_ids = Arr::get($args, 'collection_ids');
        $post_ids = Arr::get($args, 'post_ids');
        foreach ($post_ids as $post_id) {
            $post = Post::find($post_id);
            if ($post) {
                $post->collectable($collection_ids);
                $post->save();
            }
        }
        return true;
    }

    // 从合集中移除动态
    public function resolveMoveOutCollection($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $collection_ids = Arr::get($args, 'collection_ids');
        $post_ids = Arr::get($args, 'post_ids');
        foreach ($collection_ids as $collection_id) {
            $collection = Collection::find($collection_id);
            if ($collection) {
                $collection->cancelCollectByPostIds($post_ids);
            }
        }
        return true;
    }

    public function resolverPosts($rootValue, $args, $context, $resolveInfo)
    {

        $order      = data_get($args, 'order');

        $qb = $rootValue->posts()->publish();

        $qb->when($order == 'LATEST', function ($q) {
            $q->orderByDesc('id');
        });

        return $qb;
    }

    public function resolveSearchCollections($rootValue, $args, $context, $resolveInfo)
    {
        return static::search(data_get($args, 'query'));
    }
}
