<?php

namespace Haxibiao\Content\Traits;

use App\Action;
use App\AppConfig;
use App\Collection;
use App\Comment;
use App\Gold;
use App\Image;
use App\Spider;
use App\User;
use App\Visit;
use Haxibiao\Breeze\Exceptions\GQLException;
use Haxibiao\Content\Constracts\Collectionable;
use Haxibiao\Content\Jobs\PublishNewPosts;
use Haxibiao\Content\Post;
use Haxibiao\Content\PostRecommend;
use Haxibiao\Helpers\Facades\SensitiveFacade;
use Haxibiao\Helpers\utils\BadWordUtils;
use Haxibiao\Helpers\utils\QcloudUtils;
use Haxibiao\Media\Events\PostPublishSuccess;
use Haxibiao\Media\Jobs\ProcessVod;
use Haxibiao\Media\Video;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
trait PostRepo
{

    //创建post（video和image），不处理issue问答创建了
    public function resolveCreateContent($root, array $args, $context, $info)
    {

        if (in_array(config('app.name'), ['dongmeiwei', 'yinxiangshipin', 'caohan'])) {
            $islegal = SensitiveFacade::islegal(Arr::get($args, 'body'));
            if ($islegal) {
                throw new GQLException('发布的内容中含有包含非法内容,请删除后再试!');
            }
        } else {
            if (BadWordUtils::check(Arr::get($args, 'body'))) {
                throw new GQLException('发布的内容中含有包含非法内容,请删除后再试!');
            }
        }
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

        //FIXME:  安保联盟的 tag_id 与 category_ids 同含义
        if ('ablm' == config('app.name')) {

            $inputs = [
                'body'         => Arr::get($args, 'body'),
                'gold'         => Arr::get($args, 'issueInput.gold', 0),
                'tag_id'       => Arr::get($args, 'category_ids', null),
                'images'       => Arr::get($args, 'images', null),
                'video_id'     => Arr::get($args, 'video_id', null),
                'qcvod_fileid' => Arr::get($args, 'qcvod_fileid', null),
            ];
        }
        //FIXME:  yyjieyou的 tag_id 与 category_ids 同含义
        if ('yyjieyou' == config('app.name')) {
            $arr    = $args['category_ids'] ?? null;
            $tag_id = $arr['0'];
            $inputs = [
                'body'     => Arr::get($args, 'body'),
                'tag_id'   => $tag_id,
                'video_id' => Arr::get($args, 'video_id', null),

            ];
        }
        $post = Post::createPost($inputs);

        $tagNames = data_get($args, 'tag_names', []);
        if ($tagNames) {
            //答转tag表可能还有用，先不存标签
            if (!env('APP_NAME') == "datizhuanqian") {
                $post->tagByNames($tagNames);
            }

            $post->save();
        }

        return $post;
    }

    /**
     * 创建动态
     * body:    文字描述
     * category_ids:    话题
     * images:  base64图片
     * video_id: 视频ID
     */
    public static function createPost($inputs)
    {
        if (in_array(config('app.name'), ['dongmeiwei', 'yinxiangshipin', 'caohan'])) {
            $islegal = SensitiveFacade::islegal(data_get($inputs, 'body'));
            if ($islegal) {
                throw new GQLException('发布的内容中含有包含非法内容,请删除后再试!');
            }
        }

        try {
            $user = getUser();
            if ($user->isBlack()) {
                throw new GQLException('发布失败,你以被禁言');
            }

            //带视频
            $video_id     = $inputs['video_id'] ?? null;
            $qcvod_fileid = $inputs['qcvod_fileid'] ?? null;
            $body         = $inputs['body'] ?? null;
            $images       = $inputs['images'] ?? null;
            $shareLink    = data_get($inputs, 'share_link');

            if ($shareLink) {
                throw_if(is_null($qcvod_fileid), GQLException::class, '收藏失败,请稍后重试!');

                $videoInfo = QcloudUtils::getVideoInfo($qcvod_fileid);
                throw_if(is_null($videoInfo), GQLException::class, '收藏失败,请稍后重试!');

                //                //精力点校验
                //                throw_if($user->ticket < 1, UserException::class, '分享视频失败,精力点不足,请补充精力点!');

                $sourceVideoUrl = data_get($videoInfo, 'basicInfo.sourceVideoUrl');
                $dyUrl          = Spider::extractURL($shareLink);
                $result         = @file_get_contents('http://media.haxibiao.com/api/v1/spider/parse?share_link=' . $dyUrl);
                throw_if(!$result, GQLException::class, '收藏失败,请稍后重试!');

                $result = json_decode($result);
                $video  = Video::firstOrNew([
                    'hash' => hash_file('md5', $sourceVideoUrl),
                ]);
                if (!$video->exists) {
                    $video->user_id      = $user->id;
                    $video->qcvod_fileid = $qcvod_fileid;
                    $video->path         = $sourceVideoUrl;
                    $video->disk         = 'vod';
                    $video->created_at   = now();
                    $video->updated_at   = now();
                    $video->saveDataOnly();

                    $coversUrl = data_get($result, 'raw.raw.item_list.0.video.origin_cover.url_list.0');
                    $imagePath = 'images/' . genrate_uuid('jpg');
                    Storage::cloud()->put($imagePath, file_get_contents($coversUrl));

                    $dynamicCoverUrl  = data_get($result, 'raw.raw.item_list.0.video.dynamic_cover.url_list.0');
                    $dynamicCoverPath = 'images/' . genrate_uuid('webp');
                    Storage::cloud()->put($dynamicCoverPath, file_get_contents($dynamicCoverUrl));

                    $width    = data_get($result, 'raw.raw.item_list.0.video.width');
                    $height   = data_get($result, 'raw.raw.item_list.0.video.height');
                    $duration = data_get($result, 'raw.raw.item_list.0.video.duration', 0);

                    $video->json = [
                        'cover'          => cdnurl($imagePath),
                        'width'          => $width,
                        'height'         => $height,
                        'duration'       => intval($duration / 1000),
                        'sourceVideoUrl' => $sourceVideoUrl,
                        'dynamic_cover'  => cdnurl($dynamicCoverPath),
                        'share_link'     => $dyUrl,
                    ];
                    $video->status = Video::TRANSCODE_STATUS;
                    $video->saveDataOnly();
                }

                $model = Spider::where('spider_id', $video->id)
                    ->first();
                $sourceUrl = data_get($model, 'source_url');
                if ($sourceUrl && ($sourceUrl != $dyUrl)) {
                    throw new GQLException('上传的参数有误');
                }

                $spider = Spider::firstOrNew([
                    'source_url' => $dyUrl,
                ]);
                if (!$spider->exists) {
                    $spider->data = [
                        'title' => $body,
                        'raw'   => data_get($result, 'raw.raw'),
                    ];
                    $spider->status      = Spider::PROCESSED_STATUS;
                    $spider->spider_type = 'videos';
                    $spider->spider_id   = $video->id;
                    $spider->created_at  = now();
                    $spider->updated_at  = now();
                    $spider->saveDataOnly();
                }
                $post = Post::firstOrNew([
                    'user_id'  => $user->id,
                    'video_id' => $video->id,
                ]);
                if (!$post->exists) {
                    $post->content    = $body;
                    $post->status     = Post::PUBLISH_STATUS;
                    $post->spider_id  = $spider->id;
                    $post->review_id  = Post::makeNewReviewId();
                    $post->review_day = Post::makeNewReviewDay();
                    $post->save();
                    if ('dongdianyi' != (config('app.name'))) {
                        //默认添加抖音中的标签
                        self::extractTag($post);
                        // 动态是否开启默认生成合集
                        $postOpenCollection = config('haxibiao-content.post_open_collection', true);
                        if ($postOpenCollection) {
                            if ($post instanceof Collectionable) {
                                //默认添加抖音中的合集
                                self::extractCollect($post);
                            }
                        }
                    }
                    //添加定位信息
                    if (in_array(config('app.name'), ['dongwaimao', 'jinlinle']) && !empty(data_get($inputs, 'location'))) {
                        \App\Location::storeLocation(data_get($inputs, 'location'), 'posts', $post->id);
                    }
                }
                //触发更新事件-扣除精力点
                $spider->updated_at = now();
                $spider->save();
            } else {
                if ($video_id || $qcvod_fileid) {
                    if ($qcvod_fileid) {
                        //先给前端直接返回一个可播放的url
                        $videoInfo      = QcloudUtils::getVideoInfo($qcvod_fileid);
                        $defalutPath    = 'http://1254284941.vod2.myqcloud.com/e591a6cavodcq1254284941/74190ea85285890794946578829/f0.mp4';
                        $sourceVideoUrl = Arr::get($videoInfo, 'basicInfo.sourceVideoUrl', $defalutPath);
                        $video          = Video::firstOrNew([
                            'hash' => hash_file('md5', $sourceVideoUrl),
                        ]);
                        throw_if($video->exists, GQLException::class, '该视频已经被上传过啦，换一个试试');
                        $video->qcvod_fileid = $qcvod_fileid;
                        $video->user_id      = $user->id;
                        //$video->hash         = hash_file('md5',$sourceVideoUrl);
                        $video->path = $sourceVideoUrl;
                        // $video->cover = '...'; //TODO: 待王彬新 sdk 提供封面cdn url
                        $video->title = Str::limit($body, 50);
                        $video->save();
                        //创建post
                        $post           = new static();
                        $post->user_id  = $user->id;
                        $post->video_id = $video->id;
                        if ('dongdianyi' == (config('app.name'))) {
                            $post->status = Post::PUBLISH_STATUS;
                        } else {
                            $post->status = Post::PRIVARY_STATUS; //vod视频动态刚发布时是草稿状态
                        }
                        $post->content    = $body;
                        $post->review_id  = Post::makeNewReviewId();
                        $post->review_day = Post::makeNewReviewDay();
                        $post->save();
                        //添加定位信息
                        if (in_array(config('app.name'), ['dongwaimao', 'jinlinle']) && !empty(data_get($inputs, 'location'))) {
                            \App\Location::storeLocation(data_get($inputs, 'location'), 'posts', $post->id);
                        }

                        //                        $chain = [];
                        //                        if(config('haxibiao-content.enabled_video_share',false)){
                        //                            // 如果视频大于video_threshold_size,不处理metadata
                        //                            $fileSize = data_get($videoInfo,'metaData.size',null);
                        //                            $flag     = $fileSize && $fileSize < config('haxibiao-content.video_threshold_size',100*1024*1024);
                        //                            if( $flag){
                        //                                $chain = [
                        //                                    new VideoAddMetadata($video),// 修改视频的metadata信息
                        //                                ];
                        //                            }
                        //                        }
                        //                        ProcessVod::withChain($chain)->dispatch($video);
                        ProcessVod::dispatch($video);

                        // 记录用户操作
                        Action::createAction('posts', $post->id, $post->user->id);
                        // Ip::createIpRecord('users', $user->id, $user->id);
                    } else if ($video_id) {
                        $post = Post::where('video_id', $video_id)->first();
                        if (!$post) {
                            $post = new static();
                        }
                        $post->content    = $body;
                        $post->review_id  = Post::makeNewReviewId();
                        $post->review_day = Post::makeNewReviewDay();
                        $post->video_id   = $video_id; //关联上视频
                        $post->user_id    = $user->id;

                        //安保联盟post进行了分类
                        if ('ablm' == (config('app.name'))) {
                            $post->tag_id = $inputs['tag_id'][0];

                            //保证下面返回的两个字段不为Null，数据库已设置默认值为0
                            $post->count_likes    = 0;
                            $post->comments_count = 0;
                        }

                        //yyjieyou
                        if ('yyjieyou' == (config('app.name'))) {
                            $post->tag_id = $inputs['tag_id'];
                        }

                        $post->save();
                        //添加定位信息
                        if (in_array(config('app.name'), ['dongwaimao', 'jinlinle']) && !empty(data_get($inputs, 'location'))) {
                            \App\Location::storeLocation(data_get($inputs, 'location'), 'posts', $post->id);
                        }
                    }
                } else {
                    //带图片
                    $post          = new static();
                    $post->content = $body;
                    $post->user_id = $user->id;
                    $post->status  = Post::PUBLISH_STATUS;
                    $post->save();

                    //添加定位信息
                    if (in_array(config('app.name'), ['dongwaimao', 'jinlinle']) && !empty(data_get($inputs, 'location'))) {
                        \App\Location::storeLocation(data_get($inputs, 'location'), 'posts', $post->id);
                    }

                    if ($images) {
                        $imageIds = [];
                        foreach ($images as $image) {
                            $model      = Image::saveImage($image);
                            $imageIds[] = $model->id;
                        }
                        $post->images()->sync($imageIds);
                    }
                }
            }

            if (env('APP_NAME') == "dianmoge") {
                Post::createArticle($inputs, $user);
            }

            // Sync分类关系
            if ($inputs['category_ids'] ?? null) {
                $post->categorize($inputs['category_ids']);
            }
            if ($inputs['collection_ids'] ?? null) {
                $post->collectivize($inputs['collection_ids']);
            }
            if ($inputs['community_id'] ?? null) {
                $post->communities()->sync($inputs['community_id'], false);
            }

            app_track_event('发布', '发布Post动态');
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
                $qcvod_fileid = $inputs['qcvod_fileid'];
                $video        = Video::firstOrNew([
                    'qcvod_fileid' => $qcvod_fileid,
                ]);
                $video->qcvod_fileid = $qcvod_fileid;
                $video->user_id      = $user->id;
                $video->path         = 'http://1254284941.vod2.myqcloud.com/e591a6cavodcq1254284941/74190ea85285890794946578829/f0.mp4';
                $video->title        = Str::limit($inputs['body'], 50);
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

                ProcessVod::dispatch($video);
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

    /**
     * 目前最简单的错日排重推荐视频算法(FastRecommend)，人人可以看最新，随机，过滤，不重复的视频流了
     *
     * @param int $limit 返回动态条数
     * @param mixed $query 基础推荐数据范围查询
     * @param mixed $scope 推荐排重游标范围名称(视频刷，电影剪辑)
     * @return array
     */
    public static function fastRecommendPosts($limit = 4, $query = null, $scope = null)
    {
        //登录用户
        $user = getUser();

        //基础推荐数据 - 全部有视频的动态
        if (is_null($query)) {
            $query = \App\Post::has('video')->with(['video', 'user'])->publish();
        }
        $qb = $query;

        //开始推荐 - 把每天的最大指针拿进一个数组
        $maxReviewIdInDays = Post::getMaxReviewIdInDays();

        //1.过滤 过滤掉自己 和 不喜欢用户的作品
        $notLikIds = [];
        if (class_exists("App\Dislike")) {
            $notLikIds = $user->dislikes()->ByType('users')->get()->pluck('dislikeable_id')->toArray();
        }
        if (class_exists('App\UserBlock')) {
            $blockIds  = $user->userBlock()->pluck('user_block_id')->toArray();
            $notLikIds = array_unique(array_filter(array_merge($notLikIds, $blockIds)));
        }

        $notLikIds[] = $user->id; //默认不喜欢刷到自己的视频动态
        $qb          = $qb->whereNotIn('user_id', $notLikIds);

        if (in_array(config('app.name'), ['yinxiangshipin', 'caohan'])) {
            $vestIds = User::whereIn('role_id', [User::VEST_STATUS, User::EDITOR_STATUS])->pluck('id')->toArray();
            $qb      = $qb->whereIn('user_id', $vestIds);
        }

        $postRecommend = PostRecommend::fetchByScope($user, $scope);
        //2.找出指针：最新，随机 每个用户的推荐视频推荐表，就是日刷指针记录表，找到最近未刷完的指针（指针含日期和review_id）
        $reviewId  = Post::getNextReviewId($postRecommend->day_review_ids, $maxReviewIdInDays);
        $reviewDay = substr($reviewId, 0, 8);

        //视频刷光了,先返回20个最新的视频顶一下，有点逻辑需要分析
        if (is_null($reviewId)) {
            $result = $qb->latest('id')->skip(rand(1, 100))->take(20)->get();
            //视频刷的就不算访问足迹了，这个数据暂时不排重，无意义
            // Visit::saveVisits($user, $result, 'posts');
            return $qb->latest('id')->skip(rand(1, 100))->take(20)->get();
        }

        //3.取未刷完的这天的指针后的视频
        $qb = $qb->take($limit);
        $qb = $qb->where('review_day', $reviewDay)
            ->where('review_id', '>', $reviewId)
            ->orderBy('review_id');

        //获取数据
        $posts = $qb->get();

        // 视频刷光了,先返回20个最新的视频顶一下
        if (!$posts->count()) {
            // Log::channel('fast_recommend')->error($reviewId . '指针没空，结果是空' . $reviewDay);
            $withRelationList = ['video', 'user'];
            if (class_exists("App\\Role", true)) {
                $withRelationList = array_merge($withRelationList, ['user.role']);
            }

            $qb_published = Post::has('video')->with($withRelationList)->publish();

            // 印象视频有编辑发布的精品
            if (in_array(config('app.name'), ['yinxiangshipin'])) {
                $vestIds      = User::whereIn('role_id', [User::VEST_STATUS, User::EDITOR_STATUS])->pluck('id')->toArray();
                $qb_published = $qb_published->whereIn('user_id', $vestIds);
                //足迹数据排重？性能风险，重复刷劝退把，逐步优化推荐即可
                // $qb_published = $qb_published->whereNotExists(function ($query) use ($user) {
                //     $query->from('visits')
                //         ->whereRaw('posts.id = visits.visited_id')
                //         ->where('visited_type', 'posts')
                //         ->where('user_id', $user->id)
                //     ;
                // });
            }
            $result = $qb_published->latest('id')->skip(rand(1, 100))->take(20)->get();

            // 视频刷的就不算访问足迹了，这个数据暂时不排重，无意义
            // Visit::saveVisits($user, $result, 'posts');

            //增加广告展示
            $mixPosts = $result;
            if (adIsOpened()) {
                $mixPosts = Post::mixPosts($result);
            }
            return $mixPosts;
        }

        //更新点赞状态
        $posts = Post::likedPosts($user, $posts);

        //更新关注状态
        $posts = Post::followedPostsUsers($user, $posts);

        //4.更新指针
        $postRecommend->updateCursor($posts);

        //混合广告视频
        $mixPosts = $posts;
        if (adIsOpened()) {
            $mixPosts = Post::mixPosts($posts);
        }

        //视频刷的就不算访问足迹了，这个数据暂时不排重，无意义
        // Visit::saveVisits($user, $mixPosts, 'posts');

        return $mixPosts;
    }

    /**
     * 查询该刷哪天的哪个位置了...
     *
     * @param $userReviewIds 用户刷过的指针记录
     * @param $maxReviewIdInDays 全动态表里所有的每天的最大review_ids
     * @return int|mixed|null
     */
    public static function getNextReviewId($userReviewIds, $maxReviewIdInDays)
    {
        //用户每日刷的 reviewid 指针
        $reviewId      = null;
        $userReviewIds = $userReviewIds ?: [];
        rsort($userReviewIds);
        $userReviewIdsByDay = [];
        //FIXME: UserAttr(userReviewIdsByDay) = 返回用户刷过的每天的指针记录的数组

        foreach ($userReviewIds as $userDayReviewId) {
            $reviewDay = substr($userDayReviewId, 0, 8);
            //生成数组
            $userReviewIdsByDay[$reviewDay] = $userDayReviewId;
        }

        foreach ($maxReviewIdInDays as $item) {
            //当前reviewDay
            $reviewDay = $item->review_day;
            //里最大的review_id
            $maxReviewId = $item->max_review_id;

            //获取用户刷的（当前reviewDay）日指针
            $userDayReviewId = Arr::get($userReviewIdsByDay, $reviewDay);

            //未刷过该日视频
            if (is_null($userDayReviewId)) {

                $reviewId = Post::where('review_day', $reviewDay)->min('review_id') - 1;
                break;
            }

            //未刷完该日视频
            if ($maxReviewId > $userDayReviewId) {

                $reviewId = $userDayReviewId;
                break;
            }

            //刷完了改日的，查询下一天的.. 直到找到review_id
        }

        return $reviewId; //null 表示刷完了全站视频...
    }

    //粘贴时：保存抖音爬虫视频动态
    public static function saveSpiderVideoPost($spider)
    {
        $post = Post::firstOrNew(['spider_id' => $spider->id]);

        //创建动态 避免重复创建..
        if (!isset($post->id)) {
            $post->user_id = $spider->user_id;
            if (!config('app.name') == 'yinxiangshipin') {
                $post->content = Arr::get($spider->data, 'title', '');
            }
            if (config('app.name') == 'datizhuanqian') {
                $post->content     = $spider->title;
                $post->description = $spider->title;
            }
            $post->status     = Post::PRIVARY_STATUS; //草稿，爬虫抓取中
            $post->created_at = now();
            $post->save();
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
        // $post->review_id  = Post::makeNewReviewId(); //定时发布时决定，有定时任务处理一定数量或者时间后随机打乱
        // $post->review_day = Post::makeNewReviewDay();
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
        if (!$post->content) {
            $post->content = trim($description);
        }
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
        //答转tag表可能还有用，先不存标签
        if (!env('APP_NAME') == "datizhuanqian") {
            $post->tagByNames($tagNames);
        }

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
    public static function posts($user_id, $keyword = null, $type = 'VIDEO')
    {
        $qb = Post::latest('id')->publish()->where('user_id', $user_id)
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

        $shareMag = config('haxibiao-content.share_config.share_msg', '#%s/share/post/%d#, #%s#,打开【%s】,直接观看视频,玩视频就能赚钱~,');
        if (checkUser() && class_exists("App\\Helpers\\Redis\\RedisSharedCounter", true)) {
            $user = getUser();
            \App\Helpers\Redis\RedisSharedCounter::updateCounter($user->id);
            //触发分享任务
            $user->reviewTasksByClass('Share');
        }
        return sprintf($shareMag, config('app.url'), $post->id, $post->description, config('app.name_cn'));
    }

    //动态广场
    public static function publicPosts($user_id)
    {
        //排除用户拉黑（屏蔽）的用户发布的视频,排除拉黑（不感兴趣）的动态
        $userBlockId    = [];
        $articleBlockId = [];
        if (in_array(config('app.name'), ['dianyintujie'])) {
            $query = Post::publish()->has('collectable')->inRandomOrder();
            if (($user = getUser(false)) && class_exists("App\\UserBlock", true)) {
                $userBlockId    = \App\UserBlock::select('user_block_id')->whereNotNull('user_block_id')->where('user_id', $user->id)->get();
                $articleBlockId = \App\UserBlock::select('article_block_id')->whereNotNull('article_block_id')->where('user_id', $user->id)->get();

                if ($userBlockId) {
                    $query->whereNotIn('user_id', $userBlockId);
                }
                if ($articleBlockId) {
                    $query->whereNotIn('id', $articleBlockId);
                }
            }
            if ($user_id) {
                $query->where("user_id", $user_id);
            }
            return $query;
        } else {
            $query = Post::publish()
                ->whereBetWeen('created_at', [now()->subDay(7), now()])
                ->inRandomOrder();
            if ($query) {
                $query = Post::publish()->inRandomOrder();
            }
            if (($user = getUser(false)) && class_exists("App\\UserBlock", true)) {
                $userBlockId    = \App\UserBlock::select('user_block_id')->whereNotNull('user_block_id')->where('user_id', $user->id)->get();
                $articleBlockId = \App\UserBlock::select('article_block_id')->whereNotNull('article_block_id')->where('user_id', $user->id)->get();

                if ($userBlockId) {
                    $query->whereNotIn('user_id', $userBlockId);
                }
                if ($articleBlockId) {
                    $query->whereNotIn('id', $articleBlockId);
                }
            }
            if ($user_id) {
                $query->where("user_id", $user_id);
            }
            return $query;
        }
    }

    public static function newPublicPosts($user_id, $page = 1, $count = 10)
    {
        $total = $page * $count;
        // 先刷快手的视频
        $query = \App\Post::whereIn('spider_id', function ($query) {
            $query->select('id')->from('spiders')->where('spiders.source_url', 'like', 'https://v.kuaishou.com/%');
        })->publish();
        if ($query->count() < $total) {
            return Post::publicPosts($user_id);
        }

        return $query->inRandomOrder();
    }
}
