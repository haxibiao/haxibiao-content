<?php

namespace Haxibiao\Content\Traits;

use App\Action;
use App\Article;
use App\Category;
use App\Image;
use App\Issue;
use App\Jobs\AwardResolution;
use App\Tag;
use App\Video;
use App\Visit;
use Carbon\Carbon;
use DOMDocument;
use Exception;
use Haxibiao\Breeze\Exceptions\GQLException;
use Haxibiao\Breeze\Exceptions\UserException;
use Haxibiao\Breeze\Ip;
use Haxibiao\Breeze\Notifications\ReceiveAward;
use Haxibiao\Media\Spider;
use Haxibiao\Sns\Tip;
use Haxibiao\Wallet\Gold;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait ArticleRepo
{
    /**
     * 发布视频文章， 目前只有Breeze web用
     *
     * @param array $inputs 发布动态ModelPost.vue 提交的form data
     * @return Article
     */
    public function createPost($inputs)
    {
        try {
            $user = getUser();

            //视频文章
            $post = $this;

            //视频
            $video_id = $inputs['video_id'] ?? null; //这个参数全栈检查后发现没用
            //图片
            $images = $inputs['images'] ?? null;
            //文字
            $body = $inputs['body'] ?? null;

            $post->user_id = $user->id;
            $post->status  = Article::STATUS_REVIEW;
            $post->submit  = Article::REVIEW_SUBMIT;

            // 配文
            $post->description = Str::limit($body, 280);
            $post->type        = 'post';
            $post->status      = Article::STATUS_ONLINE;

            // 视频
            if ($video = Video::find($video_id)) {
                // 播放地址 $video->path 有(api/video/store)
                // 无封面？ video created已出发哈希云api 等待hook 更新即可
                //关联视频
                $post->video_id = $video->id;
            }
            $post->saveQuietly();

            // 图片
            if ($images) {
                $imageIds = [];
                foreach ($images as $image) {
                    $model      = Image::saveImage($image);
                    $imageIds[] = $model->id;
                }
                $post->images()->sync($imageIds);
            }
            return $post;
        } catch (\Exception$ex) {
            Log::error($ex->getMessage());
            if ($ex->getCode() == 0) {
                throw new GQLException('程序小哥正在加紧修复中!');
            }
            throw new GQLException($ex->getMessage());
        }
    }

    protected function createIssue($inputs)
    {
        DB::beginTransaction();
        try {
            $user = getUser();
            if ($user->isBlack()) {
                throw new GQLException('发布失败,你以被禁言');
            }
            $issue          = new Issue();
            $issue->user_id = $user->id;
            $body           = $inputs['body'];
            $issue->title   = $body;
            $issue->save();
            //视频问答
            if ($inputs['video_id'] || $inputs['qcvod_fileid']) {
                $user                 = getUser();
                $todayPublishVideoNum = $user->articles()
                    ->whereIn('type', ['post', 'issue'])
                    ->whereNotNull('video_id')
                    ->whereDate('created_at', Carbon::now())->count();
                if ($todayPublishVideoNum == 10) {
                    throw new UserException('每天只能发布10个视频动态!');
                }
                if ($inputs['video_id'] != null) {
                    $video = Video::findOrFail($inputs['video_id']);

                    //不能发布同一个视频（一模一样的视频）
                    $videoToArticle = Article::where('user_id', $user->id)
                        ->where('video_id', $video->id)
                        ->whereIn('type', ['post', 'issue'])
                        ->count();
                    if ($videoToArticle) {
                        throw new UserException('不能发布同一个视频');
                    }

                    $article              = $video->article;
                    $article->body        = $body;
                    $article->status      = Article::STATUS_ONLINE;
                    $article->description = $body;
                    $article->title       = $body;
                    //新创建的视频动态需要审核
                    $article->submit   = Article::REVIEW_SUBMIT;
                    $article->issue_id = $issue->id;
                    $article->type     = 'issue';
                    $article->save();
                } else if ($inputs['qcvod_fileid'] != null) {
                    $fileid = $inputs['qcvod_fileid'];
                    $video  = Video::firstOrNew([
                        'fileid' => $fileid,
                    ]);
                    //这个地方需要做成异步
                    $video->user_id = $user->id;
                    $video->path    = 'http://1254284941.vod2.myqcloud.com/e591a6cavodcq1254284941/74190ea85285890794946578829/f0.mp4';
                    $video->title   = Str::limit($inputs['body'], 50);
                    $video->save();

                    //创建article
                    $article              = new Article();
                    $article->status      = Article::STATUS_REVIEW;
                    $article->submit      = Article::REVIEW_SUBMIT;
                    $article->title       = Str::limit($inputs['body'], 50);
                    $article->description = Str::limit($inputs['body'], 280);
                    $article->body        = $inputs['body'];
                    $article->type        = 'issue';
                    $article->video_id    = $video->id;
                    $article->cover_path  = 'video/black.jpg';
                    $article->save();

                }
            } else if ($inputs['images']) {

                $article              = new Article();
                $article->body        = $body;
                $article->status      = Article::STATUS_ONLINE;
                $article->description = $body;
                $article->title       = $body;
                $article->issue_id    = $issue->id;
                $article->type        = 'issue';
                $article->save();

                foreach ($inputs['images'] as $image) {
                    $image = Image::saveImage($image);
                    $article->images()->attach($image->id);
                }
                $article->save();
            }
            //付费问答(金币)
            if ($inputs['gold'] > 0) {

                if ($user->gold < $inputs['gold']) {
                    throw new UserException('您的金币不足!');
                }

                //扣除金币
                // Gold::makeOutcome($user, $inputs['gold'], '悬赏问答支付');
                $user->goldWallet->changeGold(-$inputs['gold'], '悬赏问答支付');
                $issue->gold = $inputs['gold'];
                $issue->save();

                if (!empty($article)) {
                    //带图问答不用审核，直接触发奖励
                    if ($article->type == 'issue' && is_null($article->video_id)) {
                        AwardResolution::dispatch($issue)
                            ->delay(now()->addDays(7));
                    }
                } else {
                    $article = new Article([
                        'title'       => Str::limit($inputs['body'], 50),
                        'description' => Str::limit($inputs['body'], 280),
                        'body'        => $inputs['body'],
                        'type'        => 'issue',
                        'issue_id'    => $issue->id,
                        'user_id'     => $user->id,
                        'status'      => Article::STATUS_ONLINE,
                        'submit'      => Article::SUBMITTED_SUBMIT,
                    ]);
                    $article->save();
                }
            }
            //直接关联到专题
            if ($inputs['category_ids']) {
                //排除重复专题
                $category_ids = array_unique($inputs['category_ids']);
                $category_id  = reset($category_ids);
                //第一个专题为主专题
                $article->category_id = $category_id;
                $article->save();

                if ($category_ids) {
                    $article->hasCategories()->sync($category_ids);
                }
            }
            DB::commit();
            app_track_event('用户', "发布问答");
            return $article;
        } catch (\Exception$ex) {
            DB::rollBack();
            throw new GQLException($ex->getMessage());
        }
    }
    /**
     * @param UploadedFile $file
     * @return int|mixed
     * @throws \Throwable
     */
    public function saveVideoFile(UploadedFile $file)
    {
        $hash  = md5_file($file->getRealPath());
        $video = \App\Video::firstOrNew([
            'hash' => $hash,
        ]);
//        秒传
        if (isset($video->id)) {
            return $video->id;
        }

        $uploadSuccess = $video->saveFile($file);
        throw_if(!$uploadSuccess, Exception::class, '视频上传失败，请联系管理员小哥');
        return $video->id;
    }

    public function fillForJs()
    {
        if ($this->user) {
            $this->user->fillForJs();
        }

        if ($this->category) {
            $this->category->fillForJs();
        }

        $this->description = $this->summary;
        $this->cover       = $this->getCoverAttribute();

        if ($this->video) {
            $this->duration     = gmdate('i:s', $this->video->duration);
            $this->cover        = $this->video->cover_url;
            $this->video->cover = $this->video->cover_url;

            //继续兼容旧vue
            $this->has_image     = $this->cover;
            $this->primary_image = $this->cover;
            $this->url           = $this->url;
        }
    }

    public function recordAction()
    {
        if ($this->status > 0) {
            $action = Action::updateOrCreate([
                'user_id'         => getUserId(),
                'actionable_type' => 'articles',
                'actionable_id'   => $this->id,
                'status'          => 1,
            ]);
        }
    }

    public function parsedBody($environment = null)
    {
        $this->body = parse_image($this->body, $environment);
        $pattern    = "/<img alt=\"(.*?)\" ([^>]*?)>/is";
        preg_match_all($pattern, $this->body, $match);

        //try replace first image alt ...
        // if ($match && count($match)) {
        //     $image_first = str_replace($match[1][0], $this->title, $match[0][0]);
        //     $this->body  = str_replace($match[0][0], $image_first, $this->body);
        // }

        $this->body = parse_video($this->body);
        return $this->body;
    }

    /**
     * 处理文章正文中的图片(保存外域链接图片)
     */
    public static function saveRelatedImagesFromBody($article)
    {
        $body = $article->body;
        if (blank($body)) {
            return;
        }

        //找到正文所有图片
        $imageUrls = static::findHtmlImageUrls($body);

        //检查图片和外链
        $images = [];
        foreach ($imageUrls as $url) {
            //TODO: 修复base64图片在正文
            if (str_contains($url, 'base64')) {
                continue;
            }

            //跳过非外域图片(当前host/cdn域名)
            if (str_contains($url, request()->getHost()) || str_contains($url, cdn_domain())) {
                continue;
            }

            //跳过已保存
            $image_path = parse_url($url, PHP_URL_PATH);
            if ($image = Image::where('path', $image_path)->first()) {
                $images[$url] = $image;
                continue;
            }

            try {
                // 保存外链图片
                $image        = Image::saveImage($url);
                $images[$url] = $image;

                // 替换外域图片
                $body = str_replace($url, data_get($image, 'url'), $body);

            } catch (\Exception$e) {
                error_log($e->getMessage());
            }
        }

        // 更新图片关系 默认第一图为主配图
        $article->images()->attach(data_get($images, '*.id', []));
        if (blank($article->image_id)) {
            $article->image_id = data_get($article, 'images.0.id');
        }

        // 处理封面 默认第一图为封面
        if (blank($article->cover_path)) {
            $article->cover_path = data_get($article, 'images.0.url');
        }

        $article->body = $body;
        $article->saveQuietly();
        return $article;
    }

    public static function findHtmlImageUrls($html)
    {
        $imageUrls = [];
        if (!empty($html)) {
            try {
                $doc = new DOMDocument();
                $doc->loadHTML($html);
                $xml  = simplexml_import_dom($doc);
                $tags = $xml->xpath('//img');
                foreach ($tags as $tag) {
                    $imageUrls[] = $tag['src']->__toString();
                }
            } catch (\Throwable$ex) {

            }
        }
        return $imageUrls;
    }

    public function report($type, $reason)
    {
        $this->count_reports = $this->count_reports + 1;

        $json = json_decode($this->json);
        if (!$json) {
            $json = (object) [];
        }

        $user    = getUser();
        $reports = [];
        if (isset($json->reports)) {
            $reports = $json->reports;
        }

        $report_data = [
            'type'   => $type,
            'reason' => $reason,
        ];
        $reports[] = [
            $user->id => $report_data,
        ];

        $json->reports = $reports;
        $this->json    = json_encode($json, JSON_UNESCAPED_UNICODE);
        $this->save();
    }

    /**
     * @Desc     该文章是否被当前登录的用户收藏，如果用户没有登录将返回false
     *
     * @Author   czg
     * @DateTime 2018-06-12
     * @return   bool
     */
    public function currentUserHasFavorited()
    {
        return $this->is_favorited;
    }

    public function tip($amount, $message = '')
    {
        $user = getUser();

        //保存赞赏记录
        $data = [
            'user_id'      => $user->id,
            'tipable_id'   => $this->id,
            'tipable_type' => 'articles',
        ];

        $tip = Tip::firstOrNew($data);
        //$tip->amount  = $tip->amount + $amount;
        $tip->amount  = $amount;
        $tip->message = $message; //tips:: 当上多次，总计了总量，留言只保留最后一句，之前的应该通过通知发给用户了
        $tip->save();

        //action
        $action = \App\Action::create([
            'user_id'         => $user->id,
            'actionable_type' => 'tips',
            'actionable_id'   => $tip->id,
        ]);

        //更新文章赞赏数
        $this->count_tips = $this->tips()->count();
        $this->save();

        //赞赏消息提醒
        $this->user->notify(new \Haxibiao\Breeze\Notifications\ArticleTiped($this, $user, $tip));

        return $tip;
    }
    /**
     * @Desc     记录用户浏览记录
     * @Author   czg
     * @DateTime 2018-06-28
     * @return   [type]     [description]
     */
    public function recordBrowserHistory()
    {
        //增加点击量
        if (!isRobot()) {
            //非爬虫请求才统计热度
            $this->hits = $this->hits + 1;
            //记录浏览历史
            if (currentUser()) {
                $user = getUser();
                //如果重复浏览只更新纪录的时间戳
                $visit = Visit::firstOrNew([
                    'user_id'      => $user->id,
                    'visited_type' => str_plural($this->type),
                    'visited_id'   => $this->type == 'video' ? $this->video_id : $this->id,
                ]);
                $visit->save();
            }
            $this->timestamps = false;
            $this->saveQuietly();
            $this->timestamps = true;
        }
    }

    /**
     * @Author      XXM
     * @DateTime    2018-10-27
     * @description [上传外部链接的图片到Cos]
     * @return      [type]        [description]
     */
    public function saveExternalImage()
    {
        $images     = [];
        $image_tags = [];
        //匹配出所有Image
        if (preg_match_all('/<img.*?src=[\"|\'](.*?)[\"|\'].*?>/', $this->body, $match)) {
            $image_tags = $match[0];
            $images     = $match[1];
        }
        //过滤掉cdn链接
        $images = array_filter($images, function ($url) {
            if (!str_contains($url, env('APP_DOMAIN'))) {
                return $url;
            }
        });
        $image_tags = array_filter($image_tags, function ($url) {
            if (!str_contains($url, env('APP_DOMAIN'))) {
                return $url;
            }
        });

        //保存外部链接图片
        if ($images) {
            foreach ($images as $index => $image_url) {
                //匹配URL格式是否正常
                $regx = "/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/";
                if (preg_match($regx, $image_url)) {
                    $innerImage          = new Image();
                    $innerImage->user_id = getUserId();
                    $innerImage->save();
                    $path = $innerImage->saveRemoteImage($image_url, $this->title);

                    //替换正文Image 标签 保守办法 只替换Image
                    $new_image_tag = str_replace($image_url, $path, $image_tags[$index]);
                    $this->body    = str_replace($image_tags[$index], $new_image_tag, $this->body);
                    $this->save();
                }
            }
        }
    }

    /**
     * @Author      XXM
     * @DateTime    2018-11-11
     * @description [改变相关的动态 后期将这块放进队列中处理]
     * @return      [null]
     *
     * @deprecated ivan: 这个操作有点复杂，暂时不维护操作动态记录先，主要场景编辑文章暂时不用
     */
    public function changeAction()
    {
        return;

        //改变 发表文章的动态
        $action = $this->morphMany(\App\Action::class, 'actionable')->first();
        if ($action) {
            $action->status = $this->status;
            $action->save(['timestamps' => false]);
        }

        //改变评论 动态
        $comments = $this->comments ?? [];
        foreach ($comments as $comment) {
            $comment_action = $comment->morphMany(\App\Action::class, 'actionable')->first();
            if ($comment_action) {
                $comment_action->status = $this->status;
                $comment_action->save(['timestamps' => false]);
            }
            //改变被喜欢的评论 动态
            foreach ($comment->likes ?? [] as $comment_like) {
                $comment_like_action = $comment_like->morphMany(\App\Action::class, 'actionable')->first();
                if ($comment_like_action) {
                    $comment_like_action->status = $this->status;
                    $comment_like_action->save(['timestamps' => false]);
                }
            }
        }

        //改变喜欢 动态
        $likes = $this->likes ?? [];
        foreach ($likes as $like) {
            $like_action = $like->morphMany(\App\Action::class, 'actionable')->first();
            if ($like_action) {
                $like_action->status = $this->status;
                $like_action->save(['timestamps' => false]);
            }
        }

        //改变收藏
        $favorites = $this->favorites ?? [];
        foreach ($favorites as $favorite) {
            $favorite_action = $favorite->morphMany(\App\Action::class, 'actionable')->first();
            if ($favorite_action) {
                $favorite_action->status = $this->status;
                $favorite_action->save(['timestamps' => false]);
            }
        }
    }

    //直接收录到专题的操作
    public function saveCategories($categories_json)
    {
        $article          = $this;
        $old_categories   = $article->categories;
        $new_categories   = is_array($categories_json) ? $categories_json : @json_decode($categories_json);
        $new_category_ids = [];
        //记录选得第一个做文章的主分类，投稿的话，记最后一个专题做主专题
        if (!empty($new_categories)) {
            $article->category_id = $new_categories[0]->id;
            $article->save();

            foreach ($new_categories as $cate) {
                $new_category_ids[] = $cate->id;
            }
        }
        //sync
        $params = [];
        foreach ($new_category_ids as $category_id) {
            $params[$category_id] = [
                'submit' => '已收录',
            ];
        }
        $article->categories()->sync($params);
        // $article->categories()->sync($new_category_ids);

        //re-count
        if (is_array($new_categories)) {
            foreach ($new_categories as $category) {
                //更新新分类文章数
                if ($category = Category::find($category->id)) {
                    $category->count        = $category->publishedArticles()->count();
                    $category->count_videos = $category->videoPosts()->count();
                    $category->save();
                }
            }
        }
        foreach ($old_categories as $category) {
            //更新旧分类文章数
            $category->count        = $category->publishedArticles()->count();
            $category->count_videos = $category->videoPosts()->count();
            $category->save();
        }
    }

    /**
     * 根据抖音视频信息 转存到 公司的cos
     * FIXME  待 article 与 video 模块重构后，这也需要变化
     * @param array $info
     * @return Article
     * @author zengdawei
     */
    public function parseDouyinLink(array $info)
    {

        $status = array_get($info, 'code');
        // 判断 爬取 信息是否成功
        if ($status == 1) {

            // 爬取出来的信息
            $info = $info['0'];
            $url  = $info['play_url'];

            // 填充模型
            $hash           = md5_file($url);
            $video          = new Video();
            $video->title   = $info['aweme_id'];
            $video->hash    = $hash;
            $video->user_id = getUserId();
            $video->save();

            $cosPath = 'video/' . $video->id . '.mp4';

            try {
                //本地存一份用于截图
                Storage::disk('public')->put($cosPath, file_get_contents($url));
                $video->disk = 'local'; //先标记为成功保存到本地

                $video->path = $cosPath;
                $video->save();

                //同步上传到cos
                $cosDisk = Storage::cloud();
                $cosDisk->put($cosPath, Storage::disk('public')->get($cosPath));
                $video->disk = 'cos';
                $video->save();

                // sync 爬取信息
                $this->video_id    = $video->id;
                $this->title       = $info['desc'];
                $this->description = $info['desc'];
                $this->body        = $info['desc'];

                $this->user_id = currentUser()->id;
                $this->type    = 'video';
                $this->save();

                // 防止 gql 属性找不到
                return Article::find($this->id);

            } catch (\Exception$ex) {
                Log::error("video save exception" . $ex->getMessage());
            }

        }
        throw new Exception('分享失败，请检查您的分享信息是否正确!');
    }

    public function get_description()
    {
        return str_limit($this->description, 10);
    }

    public function get_title()
    {
        return str_limit($this->title, 10);
    }

    public function processSpider(array $data)
    {
        //同步爬虫标签
        $this->syncSpiderTags(Arr::get($data, 'raw.item_list.0.desc', ''));
        //同步爬虫视频
        $video = $this->syncSpiderVideo(Arr::get($data, 'video'));
        //创建热门分类
        $category = $this->createHotCategory();

        //发布article
        $this->type       = 'video';
        $this->video_id   = data_get($video, 'id');
        $this->cover_path = data_get($video, 'cover');
        $this->setStatus(Article::STATUS_ONLINE);
        $this->category_id = data_get($category, 'id');
        $this->save();
        $this->categories()->sync([$category->id]);

        //奖励用户
        $user = $this->user;
        if (!is_null($user)) {
            $user->notify(new ReceiveAward('发布视频动态奖励', 10, $user, $this->id));
            Gold::makeIncome($user, 10, '发布视频动态奖励');
        }
    }

    public function syncSpiderTags($description)
    {
        $description = preg_replace('/@([\w]+)/u', '', $description);
        preg_match_all('/#([\w]+)/u', $description, $topicArr);

        if ($topicArr[1]) {
            $tags = [];
            foreach ($topicArr[1] as $topic) {
                if (Str::contains($topic, '抖音')) {
                    continue;
                }
                $tag = Tag::firstOrCreate([
                    'name' => $topic,
                ], [
                    'user_id' => 1,
                ]);
                $tags[] = $tag->id;
            }
            $this->tags()->sync($tags);
        }
    }

    public function syncSpiderVideo($data)
    {
        $hash     = Arr::get($data, 'hash');
        $json     = Arr::get($data, 'json');
        $mediaUrl = Arr::get($data, 'url');
        $coverUrl = Arr::get($data, 'cover');
        if (!empty($hash)) {
            $video = Video::firstOrNew(['hash' => $hash]);
            //同步视频信息
            $video->setJsonData('metaInfo', $json);
            $video->setJsonData('server', $mediaUrl);
            $video->user_id = $this->user_id;

            //更改VOD地址
            $video->disk   = 'vod';
            $video->fileid = Arr::get($json, 'vod.FileId');
            $video->path   = $mediaUrl;
            $video->save();

            //保存视频截图 && 同步填充信息
            $video->status   = 1;
            $video->cover    = $coverUrl;
            $video->duration = Arr::get($data, 'duration');
            $video->setJsonData('cover', $coverUrl);
            $video->setJsonData('width', Arr::get($data, 'width'));
            $video->setJsonData('height', Arr::get($data, 'height'));
            $video->save();

            return $video;
        }
    }

    public function createHotCategory()
    {
        $category = Category::firstOrNew([
            'name' => '我要上热门',
        ]);
        if (!$category->id) {
            $category->name_en = 'woyaoshangremeng';
            $category->status  = Category::STATUS_PUBLISH;
            $category->user_id = 1;
            $category->save();
        }

        return $category;
    }

    public function setStatus($status)
    {
        $this->submit = $this->status = $status;
    }

    public function isSpider()
    {
        return Str::contains($this->source_url, 'v.douyin.com');
    }

    public function isReviewing()
    {
        return $this->status == Article::STATUS_REVIEW;
    }

    public function startSpider()
    {
        if ($this->isReviewing() && $this->isSpider()) {
            $data  = Spider::parse($this->source_url);
            $video = Arr::get($data, 'video');
            if (is_array($video)) {
                $this->processSpider($data);
            }
            $this->save();
        }
    }
}
