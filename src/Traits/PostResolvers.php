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

    /**
     * 最常用的发布动态接口，不需要考虑其他content的发布，文章，问答，走其他接口
     */
    public function resolveCreateContent($root, array $args, $context, $info)
    {
        //参数格式化
        $inputs = [
            'body'           => Arr::get($args, 'body'),
            'category_ids'   => Arr::get($args, 'category_ids', null),
            'product_id'     => Arr::get($args, 'product_id', null),
            'images'         => Arr::get($args, 'images', null),
            'video_id'       => Arr::get($args, 'video_id', null),
            'qcvod_fileid'   => Arr::get($args, 'qcvod_fileid', null),
            'share_link'     => data_get($args, 'share_link', null),
            'collection_ids' => data_get($args, 'collection_ids', null),
            'community_id'   => data_get($args, 'community_id', null),
            'location'       => data_get($args, 'location', null),

        ];

        //FIXME:  安保联盟的 tag_id 与 category_ids 同含义?
        // 这个前端传参小坑，不要继续留下去了，
        // 自己resolvers层修复兼容，不给createPost增加逻辑

        // 这里已经写死createPost了
        $post = Post::createPost($inputs);

        //标签处理
        $tagNames = data_get($args, 'tag_names', []);
        if ($tagNames) {
            //FIXME: 答赚tag表里有部分数据是 前端tabs定义的用途，需要和前端一起重构掉
            if (!env('APP_NAME') == "datizhuanqian") {
                $post->tagByNames($tagNames);
            }

            $post->save();
        }

        return $post;
    }

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
        if (currentUser()) {
            $visited = Visit::create([
                'visited_type' => 'users',
                'visited_id'   => data_get($args, 'user_id'),
                'user_id'      => getUserId(),
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
        $page  = $args['page'] ?? 1;
        $first = $args['first'] ?? $args['count'] ?? 10;
        $total = $page * $first;

        // 更新时间倒排 - 默认访客
        $query = \App\Post::publish()->latest('updated_at');

        //FIXME: 印象视频团队给这段代码迁移出去
        if (config('app.name') == 'yinxiangshipin') {
            // 先刷快手的视频（避免出现展示的都是几个月前的动态)
            $qb_kuaishou = $query->whereIn('spider_id', function ($query) {
                $query->select('id')->from('spiders')->where('spiders.source_url', 'like', 'https://v.kuaishou.com/%');
            });
            //如果有的话
            if ($qb_kuaishou->count()) {
                $query = $qb_kuaishou;
            }
        }

        // 登录用户，尊重个人兴趣
        if ($user = currentUser()) {
            $query = Post::publicPosts($user->id);
        }

        return $query;
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

        $query = Post::has('video');
        //1.优先来1个有电影的
        $qb = (clone $query)->where('movie_id', '>', 0);
        if (!$qb->exists()) {
            $movie_posts = Post::getRecommendPosts(1, $qb->with('movie'), '电影剪辑');
            $posts       = $posts->merge($movie_posts);
        }

        //2.再填充1个有合集的
        $qb = (clone $query)->where('collection_id', '>', 0);
        if ($qb->exists()) {
            $collection_posts = Post::getRecommendPosts(1, $qb->with('collection'), '视频合集');
            $posts            = $posts->merge($collection_posts);
        }

        //3. 再填充1个有题目的
        $qb = (clone $query)->where('question_id', '>', 0);
        if ($qb->exists()) {
            $question_posts = Post::getRecommendPosts(1, $qb->with('question'), '视频答题');
            $posts          = $posts->merge($question_posts);
        }

        //4. 最后补充普通的动态 = 也许有美女，不过影视剪辑更多..
        $qb = (clone $query)->with('movie');
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

        $user = $user_id ? User::find($user_id) : getUser();
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
