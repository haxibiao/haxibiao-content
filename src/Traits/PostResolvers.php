<?php

namespace Haxibiao\Content\Traits;

use App\Movie;
use App\Visit;
use Haxibiao\Breeze\User;
use Haxibiao\Content\Jobs\MakeMp4ByM3U8;
use Haxibiao\Content\Post;
use Haxibiao\Media\Series;
use Haxibiao\Media\Video;
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

    public function resolveFastRecommendPosts($root, $args, $context, $info)
    {
        app_track_event("首页", "推荐视频快速版");
        //标记请求为快速首页模式
        request()->request->add(['fast_recommend_mode' => true]);
        return Post::getRecommendPosts();
    }

    public function resolveRecommendPosts($root, $args, $context, $info)
    {
        app_track_event("首页", "推荐视频刷");
        return Post::getRecommendPosts();
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
        return Post::posts($args['user_id'], data_get($args, 'keyword'), $type);
    }

    public function resolveRelationQuestion($root, $args, $context, $info)
    {
        app_track_event("视频刷", "发布视频题");
        $content = $args['content'];
        $post_id = $args['post_id'];

        return Post::relationQuestion($post_id, $content);
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
        return Post::newPublicPosts($args['user_id'] ?? null, data_get($args, 'page'), data_get($args, 'count'));
    }

    /**
     * 分享视频
     */
    public function getShareLink($rootValue, array $args, $context, $resolveInfo)
    {
        app_track_event('分享', '分享视频');
        return Post::shareLink($args['id']);

        $qb = Post::latest('id');
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

        $result = Post::where('tag_id', $type)
            ->whereStatus(Post::PUBLISH_STATUS)
            ->inRandomOrder()
            ->take($limit)
            ->get();

        //第一次获取学习视频，设置第一条视频为固定视频
        if (Post::STUDY == $type && $is_first) {
            $firstPosts = Post::where('tag_id', Post::FIRST)
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
     * postWithMovies 关联电影的视频刷
     * @return void
     */
    public function postWithMovies($rootValue, array $args, $context, $resolveInfo)
    {
        //标记请求为快速推荐模式
        request()->request->add(['fast_recommend_mode' => true]);

        $limit = 4; //快速推荐有广告位逻辑

        $posts = collect([]);

        $qb = Post::has('video');
        //1.优先来1个有电影的
        $qb = (clone $qb)->where('movie_id', '>', 0);
        if (!$qb->exists()) {
            $movie_posts = Post::getRecommendPosts(1, $qb->with('movie'), '电影剪辑');
            $posts       = $posts->merge($movie_posts);
        }

        //2.再填充1个有合集的
        $qb = (clone $qb)->where('collection_id', '>', 0);
        if ($qb->exists()) {
            $collection_posts = Post::getRecommendPosts(1, $qb->with('collection'), '视频合集');
            $posts            = $posts->merge($collection_posts);
        }

        //3. 再填充1个有题目的
        $qb = (clone $qb)->where('question_id', '>', 0);
        if ($qb->exists()) {
            $question_posts = Post::getRecommendPosts(1, $qb->with('question'), '视频答题');
            $posts          = $posts->merge($question_posts);
        }

        //4. 最后补充普通的动态 = 也许有美女，不过影视剪辑更多..
        $qb = (clone $qb)->query()->with('movie');
        if ($qb->exists()) {
            $latest_take  = $limit - $posts->count();
            $latest_posts = Post::getRecommendPosts($latest_take, $qb);
            $posts        = $posts->merge($latest_posts);
        }

        return $posts;
    }

    public function resolveUpdatePost($root, $args, $context, $info)
    {
        $postId = data_get($args, 'post_id');
        $post   = Post::findOrFail($postId);
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

        if ('spider' == $filter) {
            return Post::posts($args['user_id'])->whereNotNull('spider_id');
        } elseif ('normal' == $filter) {
            return Post::posts($args['user_id'], "", "all")->whereNull('spider_id');
        }
        return Post::posts($args['user_id'], "", "all");
    }

    public function resolveSearchPosts($root, array $args, $context)
    {
        $userId       = data_get($args, 'user_id');
        $tagId        = data_get($args, 'tag_id');
        $collectionId = data_get($args, 'collection_id');
        $type         = data_get($args, 'type');
        return Post::publish()->search(data_get($args, 'query'))
            ->when('VIDEO' == $type, function ($q) use ($userId) {
                return $q->whereNotNull('video_id');
            })->when('IMAGE' == $type, function ($q) use ($userId) {
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
        $filter  = data_get($args, 'filter');
        $user_id = data_get($args, 'user_id');

        $user = checkUser() ? getUser() : User::find($user_id);
        //2.获取用户关注列表
        $followedUserIds = $user->follows()->pluck('followable_id');
        //3.获取关注用户发布的视频
        $qb = Post::whereNotNull('video_id')
            ->whereIn('user_id', $followedUserIds)
            ->orderByDesc('id');

        if (in_array(
            ['video', 'collections', 'images'],
            data_get($resolveInfo->getFieldSelection(1), 'data')
        )) {
            $qb->with(['video', 'collections', 'images']);
        }

        if ('spider' == $filter) {
            return $qb->whereNotNull('spider_id');
        } elseif ('normal' == $filter) {
            return $qb->whereNull('spider_id');
        }
        return $qb;
    }
}
