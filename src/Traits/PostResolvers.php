<?php

namespace Haxibiao\Content\Traits;

use App\Visit;
use Haxibiao\Breeze\User;
use Haxibiao\Content\Collection;
use Haxibiao\Content\Jobs\MakeMp4ByM3U8;
use Haxibiao\Content\Post;
use Haxibiao\Media\Series;
use Haxibiao\Media\Video;
use Haxibiao\Sns\Follow;
use Illuminate\Support\Arr;

trait PostResolvers
{

    public static function MakePostByMovie($rootValue, array $args, $context, $resolveInfo)
    {
        $series_id = $args['series_id'];
        $startSec  = $args['startSec'];
        $endSec    = $args['endSec'];
        $title     = $args['title'] ?? '';

        $series    = Series::find($series_id);
        $second    = $endSec - $startSec;
        $startTime = gmstrftime('%H:%M:%S', $startSec);
        $video     = Video::create([]);
        $post      = Post::create([
            'video_id' => $video->id,
            'title'    => $title,
            'status'   => Post::DELETED_STATUS,
            'user_id'  => 1,
        ]);
        dispatch_now(new MakeMp4ByM3U8($video, $series, $startTime, $second));
        return $post;
    }

    public function resolvePostByVid($rootValue, array $args, $context, $resolveInfo)
    {
        $videoIds = Video::where('vid', data_get($args, 'vid'))->get()
            ->pluck('id')
            ->toArray();
        // TODO 暂时只返回一个
        $post = \App\Post::whereIn('video_id', $videoIds)->first();
        return $post;
    }

    public function resolveRecommendPosts($root, $args, $context, $info)
    {
        app_track_event("首页", "获取学习视频");
        return static::getRecommendPosts();
    }

    public function resolvePosts($root, $args, $context, $info)
    {
        app_track_event("用户页", "我发布的视频动态");
        if (checkUser()) {
            $visited = Visit::create([
                'visited_type' => 'users',
                'visited_id'   => data_get($args, 'user_id'),
                'user_id'      => getUser()->id,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
        $type = data_get($args, 'type');
        return static::posts($args['user_id'], data_get($args, 'keyword'), $type);
    }

    /**
     * 动态广场
     */
    public function resolvePublicPosts($root, $args, $context, $info)
    {
        app_track_event("首页", "访问动态广场");
        if (in_array(config('app.name'), ['dongmeiwei'])) {
            if (checkUser()) {
                $visited = Visit::create([
                    'visited_type' => 'publicPosts',
                    'visited_id'   => 'publicPosts',
                    'user_id'      => getUser()->id,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }
        return static::newPublicPosts($args['user_id'] ?? null, data_get($args, 'page'), data_get($args, 'count'));
    }

    /**
     * 分享视频
     */
    public function getShareLink($rootValue, array $args, $context, $resolveInfo)
    {
        app_track_event('分享', '分享视频');
        return static::shareLink($args['id']);

        $qb = static::latest('id');
        //自己看自己的发布列表时，需要看到未成功的爬虫视频动态...
        if (getUserId() == $args['user_id']) {
            $qb = $qb->publish();
        }
        return $qb->where('user_id', $args['user_id']);
    }

    /**
     * 获取标签下的视频
     *
     * note:安保联盟在使用它
     * @param $rootValue
     * @param array $args
     * @param $context
     * @param $resolveInfo
     * @return mixed
     */
    public function resolvePostsByTag($rootValue, array $args, $context, $resolveInfo)
    {
        //视频类型
        $type = Arr::get($args, 'type');

        //返回的条数
        $limit = Arr::get($args, 'limit', 5);

        //是否第一次调用接口
        $is_first = Arr::get($args, 'is_first', false);

        $result = static::where('tag_id', $type)
            ->whereStatus(Post::PUBLISH_STATUS)
            ->inRandomOrder()
            ->take($limit)
            ->get();

        //第一次获取学习视频，设置第一条视频为固定视频
        if (Post::STUDY == $type && $is_first) {
            $firstPosts = static::where('tag_id', Post::FIRST)
                ->whereStatus(Post::PUBLISH_STATUS)
                ->get();

            return collect([$firstPosts, $result])->collapse();
        }

        //插入广告
        $adVideo        = $result[2];
        $adVideo->is_ad = true;
        $result[]       = $adVideo;

        return $result;
    }

    /**
     * 关注用户发布的视频
     *
     * note:安保联盟在使用它
     * @param $rootValue
     * @param array $args
     * @param $context
     * @param $resolveInfo
     * @return array|\Illuminate\Database\Eloquent\Builder
     */
    public function resolveFollowing($rootValue, array $args, $context, $resolveInfo)
    {
        //1.前置准备
        $loginUser = getUser();

        //关注类型
        $filter = 'users';

        //2.获取用户关注列表
        $followedUserIds = Follow::follows($loginUser, $filter)->pluck('followed_id');

        //3.获取关注用户发布的视频
        return static::query()
            ->whereIn('user_id', $followedUserIds)
            ->orderByDesc('created_at');
    }

    /**
     * postWithMovies 关联电影的视频刷
     * @return void
     */
    public function postWithMovies($rootValue, array $args, $context, $resolveInfo)
    {
        //关联电影不多，先不充一批影视资源的电影
        $vestIds     = User::whereIn('role_id', [User::VEST_STATUS, User::EDITOR_STATUS])->pluck('id')->toArray();
        $collections = Collection::whereIn('user_id', $vestIds)
            ->where('created_at', '>', '2020-12-18 09:18:55')
            ->inRandomOrder()
            ->take(10)
            ->get();
        $collectionPosts = [];
        foreach ($collections as $collection) {
            $collectionPosts[] = $collection->posts()->inRandomOrder()->first();
        }
        $recommendeds = Post::whereExists(
            function ($query) {
                return $query->from('link_movie')
                    ->whereRaw('link_movie.linked_id = posts.id')
                    ->where('linked_type', 'posts');
            })
            ->inRandomOrder()
            ->take(10)
            ->get();

        return $recommendeds->count() ?  $recommendeds->merge($collectionPosts):$collectionPosts ;
    }

    public function resolveUpdatePost($root, $args, $context, $info)
    {
        $postId = data_get($args, 'post_id');
        $post   = static::findOrFail($postId);
        $post->update(
            Arr::only($args, ['content', 'description'])
        );

        // 同步标签
        $tagNames = data_get($args, 'tag_names', []);

        if (!empty($tagNames)) {
            $post->retagByNames($tagNames);
        }

        return $post;
    }

    public function postByVideoId($rootValue, array $args, $context, $resolveInfo)
    {
        $videoId = data_get($args, 'video_id');
        return \App\Post::where('video_id', $videoId)->first();
    }

    public function resolveUserPosts($root, $args, $context, $info)
    {
        $filter = data_get($args, 'filter');

        if ($filter == 'spider') {
            return static::posts($args['user_id'])->whereNotNull('spider_id');
        } elseif ($filter == 'normal') {
            return static::posts($args['user_id'], "", "all")->whereNull('spider_id');
        }
        return static::posts($args['user_id'], "", "all");
    }

    public function resolveSearchPosts($root, array $args, $context)
    {
        $userId       = data_get($args, 'user_id');
        $tagId        = data_get($args, 'tag_id');
        $collectionId = data_get($args, 'collection_id');
        $type         = data_get($args, 'type');
        return static::publish()->search(data_get($args, 'query'))
            ->when($type == 'VIDEO', function ($q) use ($userId) {
                return $q->whereNotNull('video_id');
            })->when($type == 'IMAGE', function ($q) use ($userId) {
                return $q->whereNull('video_id');
            })->when($userId, function ($q) use ($userId) {
                return $q->where('posts.user_id', $userId);
            })->when($tagId, function ($q) use ($tagId) {
                return $q->whereHas('tags', function ($q) use ($tagId) {
                    $q->where('tags.id', $tagId);
                });
            })->when($collectionId, function ($q) use ($collectionId) {
                return $q->whereHas('collections', function ($q) use ($collectionId) {
                    $q->where('collections.id', $collectionId);
                });
            })->with('video');
    }

    //关注用户的收藏列表
    public function resolveFollowPosts($rootValue, array $args, $context, $resolveInfo)
    {
        $filter = data_get($args, 'filter');
        $user   = getUser();
        //2.获取用户关注列表
        $followedUserIds = $user->follows()->pluck('followed_id');
        //3.获取关注用户发布的视频
        $qb = static::whereNotNull('video_id')
            ->whereIn('user_id', $followedUserIds)
            ->orderByDesc('id');

        if (in_array(
            ['video', 'collections', 'images'],
            data_get($resolveInfo->getFieldSelection(1), 'data')
        )) {
            $qb->with(['video', 'collections', 'images']);
        }

        if ($filter == 'spider') {
            return $qb->whereNotNull('spider_id');
        } elseif ($filter == 'normal') {
            return $qb->whereNull('spider_id');
        }
        return $qb;
    }
}
