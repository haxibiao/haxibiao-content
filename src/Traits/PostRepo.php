<?php

namespace Haxibiao\Content\Traits;

use App\AppConfig;
use App\Collection;
use App\Comment;
use App\Gold;
use App\Image;
use App\Movie;
use App\Post;
use App\Question;
use App\Spider;
use App\User;
use App\Visit;
use Haxibiao\Breeze\Exceptions\GQLException;
use Haxibiao\Content\Constracts\Collectionable;
use Haxibiao\Content\Jobs\PublishNewPosts;
use Haxibiao\Helpers\Facades\SensitiveFacade;
use Haxibiao\Helpers\utils\BadWordUtils;
use Haxibiao\Media\Events\PostPublishSuccess;
use Haxibiao\Media\Video;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait PostRepo
{
    /**
     * 创建动态
     * body:    文字描述
     * category_ids:    话题
     * images:  base64图片
     * video_id: 视频ID
     */
    public static function createPost($inputs)
    {
        try {
            $user = getUser();
            // 禁言
            if ($user->isBlack()) {
                throw new GQLException('发布失败,你已被禁言');
            }

            //视频
            $video_id = $inputs['video_id'] ?? null; //这个参数全栈检查后发现没用
            $fileid   = $inputs['qcvod_fileid'] ?? null; //这是才是视频动态的标识

            //图片
            $images = $inputs['images'] ?? null;
            //文字
            $body = $inputs['body'] ?? null;
            //分享链接
            $shareLink = data_get($inputs, 'share_link');

            //清理所有冗余的过滤敏感词逻辑后，还剩下这些
            if (in_array(config('app.name'), ['dongmeiwei', 'yinxiangshipin', 'caohan'])) {
                $islegal = SensitiveFacade::islegal($body);
                if ($islegal) {
                    throw new GQLException('发布的内容中含有包含非法内容,请删除后再试!');
                }
            } else {
                if (BadWordUtils::check($body)) {
                    throw new GQLException('发布的内容中含有包含非法内容,请删除后再试!');
                }
            }

            //动态
            $post              = new Post();
            $post->description = $body;
            $post->user_id     = $user->id;
            $post->status      = Post::PUBLISH_STATUS;
            //视频

            // 本地上传video
            if ($fileid) {
                $post->fileid = $fileid;
                //主动上传视频排重
                $video = Video::firstOrNew([
                    'fileid' => $fileid,
                ]);
                if ($video->exists) {
                    //重复发布？支持！上传vod成功返回fileid不容易
                }
                $vodJson        = Video::getVodJson($fileid);
                $video->user_id = $user->id;
                //vod 播放地址
                $video->path  = data_get($vodJson, 'basicInfo.sourceVideoUrl');
                $video->title = Str::limit($body, 50);
                $video->disk  = 'vod';
                $video->save();
                $post->video_id = $video->id;
            }
            //分享视频连接方式发布作品
            if ($shareLink) {

                //秒粘贴结果
                $dyUrl            = Spider::extractURL($shareLink);
                $fastJson         = Spider::getFastDouyinVideoInfo($dyUrl);
                $video->sharelink = $dyUrl;

                //粘贴视频排重
                $video = Video::firstOrNew([
                    'sharelink' => $dyUrl,
                ]);
                $video->user_id = $user->id;
                $video->title   = Str::limit($body, 50);
                $video->disk    = 'vod';
                $video->save();

                //乐观更新 封面
                $video->json = array_merge(json_decode($video->json), [
                    'dynamic_cover' => data_get($fastJson, 'dynamic_cover'),
                    'cover'         => data_get($fastJson, 'cover'),
                ]);

                $video->saveQuietly();
            }
            $post->save();

            //图片
            if ($images) {
                $imageIds = [];
                foreach ($images as $image) {
                    $model      = Image::saveImage($image);
                    $imageIds[] = $model->id;
                }
                $post->images()->sync($imageIds);
            }

            // 专题
            if ($inputs['category_ids'] ?? null) {
                $post->addCategories($inputs['category_ids']);
            }
            // 合集
            if ($inputs['collection_ids'] ?? null) {
                $post->addCollections($inputs['collection_ids']);
            }
            // 社区
            if ($inputs['community_id'] ?? null) {
                $post->communities()->sync($inputs['community_id'], false);
            }

            //同步抖音中的标签
            Post::extractTag($post);
            // 同步合集的开关
            $postOpenCollection = config('haxibiao-content.post_open_collection', true);
            if ($postOpenCollection) {
                if ($post instanceof Collectionable) {
                    //同步抖音中的合集
                    Post::extractCollect($post);
                }
            }

            return $post;
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
            if ($ex->getCode() == 0) {
                throw new GQLException('程序小哥正在加紧修复中!');
            }
            throw new GQLException($ex->getMessage());
        }
    }

    public static function createArticle($inputs, $user)
    {
        //带视频动态
        if ($inputs['video_id'] || $inputs['qcvod_fileid']) {
            if ($inputs['video_id']) {
                $video   = Video::findOrFail($inputs['video_id']);
                $article = $video->article;
                if (!$article) {
                    $article = new \App\Article();
                }
                $article->type        = 'post';
                $article->title       = Str::limit($inputs['body'], 50);
                $article->description = Str::limit($inputs['body'], 280);
                $article->body        = $inputs['body'];
                $article->review_id   = \App\Article::makeNewReviewId();
                $article->video_id    = $video->id; //关联上视频
                $article->save();
            } else {
                $fileid = $inputs['qcvod_fileid'];
                $video  = Video::firstOrNew([
                    'fileid' => $fileid,
                ]);
                $video->user_id = $user->id;
                $video->path    = 'http://1254284941.vod2.myqcloud.com/e591a6cavodcq1254284941/74190ea85285890794946578829/f0.mp4';
                $video->title   = Str::limit($inputs['body'], 50);
                $video->save();
                //创建article
                $article              = new \App\Article();
                $article->status      = 1;
                $article->submit      = \App\Article::REVIEW_SUBMIT;
                $article->title       = Str::limit($inputs['body'], 50);
                $article->description = Str::limit($inputs['body'], 280);
                $article->body        = $inputs['body'];
                $article->type        = 'post';
                $article->review_id   = \App\Article::makeNewReviewId();
                $article->video_id    = $video->id;
                $article->cover_path  = 'video/black.jpg';
                $article->save();

            }

            //存文字动态或图片动态
        } else {
            $article              = new \App\Article();
            $body                 = $inputs['body'];
            $article->body        = $body;
            $article->description = Str::limit($body, 280); //截取微博那么长的内容存简介
            $article->type        = 'post';
            $article->user_id     = $user->id;
            $article->save();

            if ($inputs['images']) {
                foreach ($inputs['images'] as $image) {
                    $image = Image::saveImage($image);
                    $article->images()->attach($image->id);
                }

                $article->cover_path = $article->images()->first()->path;
                $article->save();
            }
        };
        if (isset($inputs['product_id'])) {
            $article->update(['product_id' => $inputs['product_id']]);
        }
    }

    /**
     * @deprecated
     */
    public static function getHotPosts($user, $limit = 10, $offset = 0)
    {
        $hasLogin = !is_null($user);
        $limit    = $limit >= 10 ? 8 : 4;

        $withRelationList = ['video', 'user'];
        if (class_exists("App\\Role", true)) {
            $withRelationList = array_merge($withRelationList, ['user.role']);
        }
        //构建查询
        $qb = Post::with($withRelationList)->has('video')->publish()
            ->orderByDesc('review_id')
            ->take($limit);
        //存在用户
        if ($hasLogin) {
            //过滤掉自己 和 不喜欢用户的作品
            $notLikIds   = $user->dislikes()->ByType('users')->get()->pluck('dislikeable_id')->toArray();
            $notLikIds[] = $user->id;
            $qb          = $qb->whereNotIn('user_id', $notLikIds);

            //排除浏览过的视频
            $visitVideoIds = Visit::ofType('posts')->ofUserId($user->id)->get()->pluck('visited_id');
            if (!is_null($visitVideoIds)) {
                $qb = $qb->whereNotIn('id', $visitVideoIds);
            }
        } else {
            //游客浏览翻页
            //访客第一页随机略过几个视频
            $offset = 0 == $offset ? mt_rand(0, 50) : $offset;
            $qb     = $qb->skip($offset);
        }
        //获取数据
        $posts = $qb->get();

        if ($hasLogin) {
            //喜欢状态
            $posts = Post::likedPosts($user, $posts);

            //关注动态的用户
            $posts = Post::followedPostsUsers($user, $posts);

            //批量插入
            Visit::saveVisits($user, $posts, 'posts');
        }

        //第二页混淆一下 防止重复的靠前
        // if ($offset > 0) {
        //     $posts = $posts->shuffle();
        // }

        //混合广告视频
        $mixPosts = Post::mixPosts($posts);

        return $mixPosts;
    }

    /**
     * 混合广告视频
     * @param $posts Collection
     */
    public static function mixPosts($posts)
    {
        //不够4个不参入广告
        if ($posts->count() < 4) {
            return $posts;
        }
        $mixPosts = [];
        $index    = 0;
        foreach ($posts as $post) {
            $index++;
            $mixPosts[] = $post;
            if ($index % 4 == 0) {
                //每隔4个插入一个广告
                $adPost          = clone $post;
                $adPost->id      = random_str(7);
                $adPost->is_ad   = true;
                $adPost->ad_type = Post::diyAdShow() ?? "tt";
                $mixPosts[]      = $adPost;
            }
        }

        return $mixPosts;
    }

    //内部广告展示
    public static function diyAdShow()
    {
        if (in_array(env('APP_NAME'), ['datizhuanqian'])) {
            $version = \App\Helpers\AppHelper::version();
            if (!empty($version) && $version->gte('3.6.0')) {
                //nova配置的内部广告展示权重
                $adConfigs = AppConfig::where('group', '广告权重')->pluck('value', 'name')->toArray();

                //返回根据权重随机的广告类型
                return countWeight($adConfigs);
            }
        }
        return "tt";
    }

    public static function followedPostsUsers($user, $posts)
    {
        $userIds = $posts->pluck('user_id');
        if (count($userIds) > 0) {
            //一次查询用户关注过的uids
            $followedUserIds = $user->followedUserIds($userIds);
            //批量更新对用户列表的 is_followed 状态
            $posts->each(function ($post) use ($followedUserIds) {
                $postUser = $post->user;
                if (!is_null($postUser)) {
                    $is_followed                    = $followedUserIds->contains($postUser->id);
                    $postUser->followed_user_status = $is_followed;
                    $postUser->is_followed          = $is_followed;
                    $postUser->followed_status      = $is_followed;
                }
            });
        }

        return $posts;
    }

    //粘贴时：保存抖音爬虫视频动态
    public static function saveSpiderVideoPost($spider)
    {
        $post = Post::firstOrNew(['spider_id' => $spider->id]);

        //创建动态 避免重复创建..
        if (!isset($post->id)) {
            $post->user_id     = $spider->user_id;
            $post->description = $spider->title;
            $post->status      = Post::PRIVARY_STATUS; //草稿，爬虫抓取中
            $post->created_at  = now();
            //视频
            $post->video_id = $spider->spider_id;
            $post->saveQuietly();
        }
    }

    //抖音爬虫成功时：发布视频动态
    public static function publishSpiderVideoPost($spider)
    {
        $post = Post::where(['spider_id' => $spider->id])->first();
        if ($post) {
            $post->video_id = $spider->spider_id; //爬虫的类型spider_type="videos",这个video_id只有爬虫成功后才有...
            Post::publishPost($post);

            // 延迟发布评论
            dispatch(function () use ($post, $spider) {
                Post::publishComment($post, $spider);
            })->onQueue('default')->delay(now()->addHours(2));
        }
    }

    /**
     * 发布动态，随机归档，奖励...
     */
    public static function publishPost($post)
    {
        if ('dongdianyi' != (config('app.name'))) {
            self::extractTag($post);
            // 动态是否开启默认生成合集
            $postOpenCollection = config('haxibiao-content.post_open_collection', true);
            if ($postOpenCollection) {
                self::extractCollect($post);
            }
        }
        $post->status = Post::PUBLISH_STATUS; //发布成功动态

        if (config('app.name') == 'ablm') {
            $post->tag_id = 2;
        }
        // PostObserver自动更新快速推荐排序游标
        $post->save();

        //FIXME: 这个逻辑要放到 content 系统里，PostObserver updated ...
        //超过100个动态或者已经有1个小时未归档了，自动发布.
        $canPublished = Post::where('review_day', 0)
            ->where('created_at', '<=', now()->subHour())->exists()
        || Post::where('review_day', 0)->count() >= 100;

        if ($canPublished) {
            dispatch_now(new PublishNewPosts);
        }

        //抖音爬的视频，可直接奖励
        // 推荐这里改成通过event来监听实现不同项目的奖励发放
        event(new PostPublishSuccess($post));
        $user = $post->user;
        if (!is_null($user) && config('app.name') != 'datizhuanqian') {
            /**
             * caohan,yxsp取消粘贴抖音视频的积分奖励
             * http://pm3.haxibiao.com:8080/browse/YXSP-93
             */
            if (!in_array(config('app.name'), ['caohan', 'yinxiangshipin', 'ainicheng'])) {
                //触发奖励
                if (2 == $user->id) {
                    Gold::makeIncome($user, 6, '测试分享视频奖励');
                } else {
                    Gold::makeIncome($user, 10, '分享视频奖励');
                }
            }
            //扣除精力-1
            if ($user->ticket > 0) {
                $user->decrement('ticket');
            }
        }
    }

    /**
     * 关联评论
     */
    public static function publishComment($post, $spider)
    {
        $dateList = create_date_array(now()->subHours(2), now(), 15);
        $dateList = array_pluck($dateList, 'time');
        // 获取随机时间
        $commentList = data_get($spider, 'data.comment.data.shortVideoCommentList.commentList', []);
        $userIds     = User::where('role_id', User::VEST_STATUS)
            ->pluck('id')
            ->toArray();
        shuffle($userIds);
        foreach ($commentList as $comment) {
            $likedCount = data_get($comment, 'likedCount');
            if ($likedCount < 100) {
                continue;
            }
            $content = data_get($comment, 'content');
            if (str_contains($content, '@')) {
                continue;
            }
            $content = preg_replace('/\[.*?\]/', '', $content);
            $content = str_replace(['快手', '快看'], '', $content);
            $content = trim($content);
            if (!$content) {
                continue;
            }
            $createAt = array_shift($dateList);
            if (!$createAt) {
                return;
            }
            $commentModel                   = new Comment();
            $commentModel->user_id          = array_shift($userIds);
            $commentModel->commentable_type = 'posts';
            $commentModel->commentable_id   = $post->id;
            $commentModel->body             = $content;
            $commentModel->created_at       = $createAt;
            $commentModel->updated_at       = $createAt;
            $commentModel->save(['timestamps' => false]);
            $i              = 0;
            $subCommentList = data_get($comment, 'subComments');
            foreach ($subCommentList as $subComment) {
                // 只抓取前三条回复
                if ($i >= 3) {
                    return;
                }
                $subCommentContent = data_get($subComment, 'content');
                if (str_contains($subCommentContent, '@')) {
                    continue;
                }
                $subCommentContent = preg_replace('/\[.*?\]/', '', $subCommentContent);
                $subCommentContent = str_replace(['快手', '快看'], '', $subCommentContent);
                if (!$subCommentContent) {
                    continue;
                }
                $createAt = array_shift($dateList);
                if (!$createAt) {
                    return;
                }

                $subCommentModel                   = new Comment();
                $subCommentModel->user_id          = array_shift($userIds);
                $subCommentModel->commentable_type = 'comments';
                $subCommentModel->commentable_id   = $commentModel->id;
                $subCommentModel->body             = $subCommentContent;
                $subCommentModel->created_at       = $createAt;
                $subCommentModel->updated_at       = $createAt;
                $subCommentModel->save(['timestamps' => false]);
                $i++;
            }
        }
    }

    public static function extractTag($post)
    {
        $spider = $post->spider;
        if (!$spider) {
            return;
        }
        $tagNames    = [];
        $tagList     = data_get($spider, 'data.raw.item_list.0.text_extra', []);
        $shareTitle  = data_get($spider, 'data.raw.item_list.0.share_info.share_title');
        $description = str_replace(['#在抖音，记录美好生活#', '@抖音小助手', '抖音', '@DOU+小助手', '快手', '#快手创作者服务中心', ' @快手小助手', '#快看'], '', $shareTitle);
        //抖音元数据存spider data上了
        foreach ($tagList as $tag) {
            $name = $tag['hashtag_name'];
            //移除关键词
            if (!$name) {
                continue;
            }
            $name        = Str::replaceArray('抖音', [''], $name);
            $description = Str::replaceFirst('#' . $name, '', $description);
            $tagNames[]  = $name;
        }
        if (!$post->description) {
            $post->description = trim($description);
        }

        // 标签
        $post->tagByNames($tagNames);
        $post->save();
    }

    //默认添加抖音中的合集
    public static function extractCollect($post)
    {
        $spider = $post->spider;
        if (!$spider) {
            return;
        }
        $mixInfo = data_get($spider, 'data.raw.item_list.0.mix_info');
        if (!$mixInfo) {
            return;
        }

        $name       = data_get($mixInfo, 'mix_name');
        $user_id    = checkUser() ? getUser()->id : $post->user_id;
        $img        = data_get($mixInfo, 'cover_url.url_list.0');
        $collection = Collection::firstOrNew([
            'name'    => $name,
            'user_id' => $user_id,
        ]);
        if (!$collection->exists) {
            if ($img) {
                $img = Image::saveImage($img);
            }
            $collection->forceFill([
                'description' => data_get($mixInfo, 'desc') ?? "",
                'logo'        => data_get($img, 'path'),
                'type'        => 'posts',
                'status'      => Collection::STATUS_ONLINE,
                'json'        => [
                    'mix_info' => $mixInfo,
                ],
            ])->save();
        }

        $collection->posts()
            ->syncWithoutDetaching([
                $post->id => [
                    'sort_rank' => data_get($mixInfo, 'statis.current_episode'),
                ],
            ]);
        $collection->updateCountPosts();
    }

    //个人主页动态
    public static function posts($user_id, $keyword = null, $type = null)
    {

        //用户本人可查看未发布的动态
        if (checkUser() && getUser()->id == $user_id) {
            $qb = Post::query();
        } else {
            $qb = Post::publish();
        }
        $qb = $qb->latest('id')->where('user_id', $user_id)
            ->when('VIDEO' == $type, function ($q) {
                return $q->whereNotNull('video_id');
            })->when('IMAGE' == $type, function ($q) {
            return $q->whereNull('video_id');
        });
        if (!empty($keyword)) {
            $qb = $qb->where('description', 'like', "%{$keyword}%");
        }
        return $qb;
    }

    //分享post链接
    public static function shareLink($id)
    {
        $post = Post::find($id);
        throw_if(is_null($post), GQLException::class, '该动态不存在哦~,请稍后再试');

        $shareMag = config('haxibiao-content.share_config.share_msg', '%s/share/post/%d?s=#%s#,打开【%s】,直接观看视频,玩视频就能赚钱~,');

        //FIXME: 直接用redis逻辑的,都先用普通Cache Facade !!!!
        // if (checkUser() && class_exists("App\\Helpers\\Redis\\RedisSharedCounter", true)) {
        //     $user = getUser();
        //     \App\Helpers\Redis\RedisSharedCounter::updateCounter($user->id);
        //     //触发分享任务
        //     $user->reviewTasksByClass('Share');
        // }

        return sprintf($shareMag, config('app.url'), $post->id, $post->description, config('app.name_cn'));
    }

    /**
     * 动态广场 - 尊重用户喜欢
     */
    public static function publicPosts($user_id)
    {
        //排除用户拉黑（屏蔽）的用户发布的视频,排除拉黑（不感兴趣）的动态
        $query = Post::publish()->latest('updated_at');
        //尊重用户拉黑的人和不感兴趣的动态
        if ($user_id && class_exists("App\\UserBlock", true)) {
            $userBlockId = \App\UserBlock::where("user_id", $user_id)
                ->where('blockable_type', 'users')
                ->pluck('blockable_id')
                ->toArray();

            $postBlockId = \App\UserBlock::where("user_id", $user_id)
                ->where('blockable_type', 'posts')
                ->pluck('blockable_id')
                ->toArray();

            if ($userBlockId) {
                $query->whereNotIn('user_id', $userBlockId);
            }
            if ($postBlockId) {
                $query->whereNotIn('id', $postBlockId);
            }
        }
        return $query;
    }

    public static function relationQuestion($post_id, $content)
    {
        $post = Post::find($post_id);
        throw_if(empty($post), GQLException::class, "该动态不存在!");
        throw_if(!empty($post->question), GQLException::class, "该动态已关联视频题!");
        //有这部电影，直接关联上
        $movie = Movie::where('name', $content)->first();

        $data = [];
        if ($movie && empty($post->movie)) {
            $data = array_add($data, 'movie_id', $movie->id);
        }

        //创建题目
        //随机一个题目
        $other_movie = null;
        do {
            $other_movie = Movie::find(random_int(1, 10000));
        } while ($other_movie == null);

        $selections = json_encode(["A" => $content, "B" => $other_movie->name], JSON_UNESCAPED_UNICODE);

        $question = Question::create([
            "description" => Question::POST_QUESTION_DESCRIPTION,
            "selections"  => $selections,
            "answer"      => $content,
            "gold"        => Question::POST_QUESTION_GOLD,
            "ticket"      => Question::POST_QUESTION_TICKET,
            "rank"        => 1,
            "status"      => Question::SUBMITTED_SUBMIT,
        ]);
        $data = array_add($data, 'question_id', $question->id);
        $post->update($data);

        return $post;
    }

    public function fillForJs()
    {
        $this->cover = $this->cover;
    }
}
