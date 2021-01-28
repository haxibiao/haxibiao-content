<?php

namespace Haxibiao\Content\Traits;

use App\Article;
use App\Scopes\ArticleSubmitScope;
use App\Visit;
use GraphQL\Type\Definition\ResolveInfo;
use Haxibiao\Breeze\Exceptions\GQLException;
use Haxibiao\Breeze\UserBlock;
use Haxibiao\Content\Post;
use Haxibiao\Helpers\Facades\SensitiveFacade;
use Haxibiao\Helpers\utils\BadWordUtils;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

trait ArticleResolvers
{
    public function articles($root, array $args, $context)
    {
        //排除用户拉黑（屏蔽）的用户发布的视频,排除拉黑（不感兴趣）的动态
        $userBlockId    = [];
        $articleBlockId = [];
        if ($user = checkUser()) {
            $userBlockId    = UserBlock::select('user_block_id')->whereNotNull('user_block_id')->where('user_id', $user->id)->get();
            $articleBlockId = UserBlock::select('article_block_id')->whereNotNull('article_block_id')->where('user_id', $user->id)->get();
        }

        $query = Article::withoutGlobalScope(ArticleSubmitScope::class)
            ->whereIn('type', ['video', 'post', 'issue'])
            ->orderBy('id', 'desc');

        if ($userBlockId) {
            $query->whereNotIn('user_id', $userBlockId);
        }
        if ($articleBlockId) {
            $query->whereNotIn('id', $articleBlockId);
        }

        if ($args['submit'] != 10) {
            $query->where('submit', $args['submit'])->whereNotNull('cover_path');
        }

        if ($args['status'] != 10) {
            return $query->where('status', $args['status'])->whereNotNull('cover_path');
        }
        $query->when(isset($args['user_id']), function ($q) use ($args) {
            return $q->where('user_id', $args['user_id']);
        });
        $query->when(isset($args['category_id']), function ($q) use ($args) {
            return $q->where('category_id', $args['category_id']);
        });
        return $query;
    }
    public function createContent($root, array $args, $context)
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
            'body'         => Arr::get($args, 'body'),
            'gold'         => Arr::get($args, 'issueInput.gold', 0),
            'category_ids' => Arr::get($args, 'category_ids', null),
            'images'       => Arr::get($args, 'images', null),
            'video_id'     => Arr::get($args, 'video_id', null),
            'qcvod_fileid' => Arr::get($args, 'qcvod_fileid', null),
        ];
        switch ($args['type']) {
            case 'issue':
                return $this->createIssue($inputs);
                break;
            case 'post':
                return $this->createPost($inputs);
                break;
            default:
                break;
        }
    }

    public function resolveTrashArticles($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = getUser();
        return $user->removedArticles();
    }

    public function restoreArticle($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $article = Article::findOrFail($args['id']);
        $article->update(['status' => 0]);
        $article->changeAction();

        return $article;
    }

    public function deleteArticle($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $post = Post::findOrFail($args['id']);
        $post->delete();
        return $post;
    }

    public function resolvePendingArticles(
        $rootValue,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo
    ) {
        $user       = getUser();
        $articles   = [];
        $categories = isset($args['category_id']) ? [\App\Category::find($args['category_id'])] : $user->adminCategories;

        foreach ($categories as $category) {
            $result = $category->newRequestArticles()->get();
            foreach ($result as $article) {
                $articles[] = $article;
            }
        }
        return $articles;
    }

    public function resolveRecommendArticles(
        $rootValue,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo
    ) {
        //FIXME: 日后真的按当前登录用户改进推荐算法...
        $qb = \App\Article::whereStatus(1)
            ->whereNotNull('title')
            ->whereNotNull('cover_path');
        return $qb->latest('id');
    }

    public function resolveFollowedArticles(
        $rootValue,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo
    ) {
        //TODO: 关注的文集，人的文章还没加入...
        $user     = \App\User::findOrFail($args['user_id']);
        $cate_ids = $user->followingCategories()->pluck('followable_id');
        $qb       = self::whereIn('category_id', $cate_ids);
        return $qb;
    }

    public function resolveCreatePost($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $article = new \App\Article();
        $article->createPost($args);

        //直接关联到专题
        if (!empty($args['category_ids'])) {
            //排除重复专题
            $category_ids = array_unique($args['category_ids']);
            $category_id  = reset($category_ids);
            array_shift($category_ids);

            //第一个专题为主专题
            $article->category_id = $category_id;
            $article->save();

            if ($category_ids) {
                $article->categories()->sync($category_ids);
            }
        }

        return $article;
    }

    public function resolveRecommendVideos(
        $rootValue,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo
    ) {
        $user      = checkUser();
        $pageCount = $args['count'];

        $qb = Article::with(['video', 'user', 'categories'])->whereNotNull('video_id')->publish()->orderByDesc('review_id');

        if ($user) {

            $qb->whereNotIn('id', function ($query) use ($user) {
                $query->select('visited_id')->from('visits')
                    ->where('visits.visited_type', 'articles')
                    ->where('visits.user_id', $user->id);
            });

            //过滤拉黑的用户的动态
            $qb->whereNotIn('user_id', function ($query) use ($user) {
                $query->select('user_block_id')->from('user_blocks')->whereNotNull('user_block_id')->where('user_id', $user->id);
            });

            //过滤不感兴趣的动态
            // $qb->whereNotIn('id', function ($query) use ($user){
            //     $query->select('article_block_id')->from('user_blocks')->whereNotNull('article_block_id')->where("user_id", $user->id);
            // });
        }
        $total = $qb->count();

        //50%概率获取热门视频
        $seed        = random_int(1, 2);
        $dataFromHot = $seed % 2 == 1;
        if ($dataFromHot) {
            $newQb          = clone $qb;
            $isHotRecommand = $newQb->where('is_hot', true)->count() > 4;
            if ($isHotRecommand) {
                //获取热门标签
                $qb = $qb->where('is_hot', true);
            }
        }

        //分页角标
        if (!$user && !$dataFromHot) {
            $offset = mt_rand(0, 50);
            $qb     = $qb->skip($offset);
        }

        $limit = $pageCount >= 10 ? 8 : 4;
        $qb    = $qb->take($limit);

        $articles = $qb->get();

        if ($user) {
            Visit::saveVisits($user, $articles, 'articles');
        }

        $mixPosts = $articles;
        //广告开关判断
        if (adIsOpened()) {
            $mixPosts = [];
            $index    = 0;
            foreach ($articles as $article) {
                $index++;
                $mixPosts[] = $article;

                if ($index % 4 == 0) {
                    $article               = clone $article;
                    $article->id           = random_str(7);
                    $article->isAdPosition = true;
                    $mixPosts[]            = $article;
                }
            }
        }

        $result = new \Illuminate\Pagination\LengthAwarePaginator($mixPosts, $total, $pageCount, $pageCount);
        return $result;
    }

    /**
     * 根据 抖音上的分享链接，爬取信息，转存到我们库中
     * 返回 article对象 时，没有cover 和 video info，需要等待队列执行完成后，信息才会更新
     * @param $rootValue
     * @param array $args
     * @param $context
     * @param $resolveInfo
     * @return void
     * @throws GQLException
     * @author zengdawei
     */
    public function resolveDouyinVideo($rootValue, array $args, $context, $resolveInfo)
    {
        $user = getUser();
        throw_if($user->isBlack(), GQLException::class, '发布失败,你以被禁言');

        try {
            $shareMsg = $args['share_link'];
            $link     = filterText($shareMsg)[0];
            //删除分享信息中违禁词
            $description = $this->deleteBadWord($shareMsg);

            //校验视频链接
            throw_if(!Str::contains($link, 'https://v.douyin.com'), GQLException::class, '您的分享链接有误哦~');

            //检查用户今日分享爬虫数量
            $this->checkTodaySpiderCount($user);

            //请求爬虫解析
            $data = $this->spiderParse($link);

            // 不允许重复视频 && 生成article数据
            $article = Article::firstOrNew([
                'source_url' => $link,
            ], [
                'user_id'     => $user->id,
                'type'        => 'post',
                'submit'      => Article::REVIEW_SUBMIT,
                'description' => $description,
                'title'       => $description,
                'body'        => $description,
                'cover_path'  => 'video/black.jpg',
                'video_id'    => 1,
            ]);
            if ($article->id) {
                if ($article->submit == Article::SUBMITTED_SUBMIT) {
                    throw new GQLException('视频已经存在，请换一个视频噢~');
                } else {
                    throw new GQLException('该视频正在审核中，请稍等噢~');
                }
            }
            //已经被处理过
            $video = Arr::get($data, 'video');
            if (is_array($video)) {
                $article->processSpider($data);
            }
            $article->save();

            return $article;
        } catch (\Exception $e) {
            if ($e->getCode() == 0) {
                Log::error($e->getMessage());
                throw new GQLException('视频上传失败,程序小哥正在处理中!');
            }
            throw new GQLException($e->getMessage());
        }
    }

    public function getShareLink($rootValue, array $args, $context, $resolveInfo)
    {
        $post = Post::has('video')->find($args['id']);
        throw_if(is_null($post), GQLException::class, '该动态不存在哦~,请稍后再试');
        $shareMag = config('haxibiao-content.share_config.share_msg', '#%s/share/post/%d#, #%s#,打开【%s】,直接观看视频,玩视频就能赚钱~,');
        if (checkUser() && class_exists("App\\Helpers\\Redis\\RedisSharedCounter", true)) {
            $user = getUser();
            \App\Helpers\Redis\RedisSharedCounter::updateCounter($user->id);
            //触发分享任务
            $user->reviewTasksByClass('Share');
        }
        if (in_array(config('app.name'), ['dongwaimao', 'jinlinle'])) {
            $post->update(['count_share' => \DB::raw('count_share'+1)]);
        }
        return sprintf($shareMag, config('app.url'), $post->id, $post->description, config('app.name_cn'));
    }

    public function deleteBadWord($description)
    {
        $description = Str::replaceFirst('#在抖音，记录美好生活#', '', $description);
        $description = Str::before($description, 'http');
        // $description = preg_replace('/@([\w]+)/u', '', $description);
        // $description = preg_replace('/#([\w]+)/u', '', $description);
        $description = str_replace(['#在抖音，记录美好生活#', '@抖音小助手', '#抖音小助手', '抖音', 'dou', 'Dou', 'DOU', '抖音助手'], '', $description);
        $description = trim($description);

        if (in_array(config('app.name'), ['dongmeiwei'])) {
            $islegal = SensitiveFacade::islegal(Arr::get($description, 'body'));
            if ($islegal) {
                throw new GQLException('发布的内容中含有包含非法内容,请删除后再试!');
            }
        } else {
            if (BadWordUtils::check(Arr::get($description, 'body'))) {
                throw new GQLException('发布的内容中含有包含非法内容,请删除后再试!');
            }
        }

        return $description;
    }

    public function checkTodaySpiderCount($user)
    {
        $todayUserUploadVideoCount = $user->articles()->whereNotNUll('source_url')
            ->whereDate('created_at', now())
            ->count();

        if (!$user->isHighRole() && $todayUserUploadVideoCount >= 30) {
            throw new GQLException('感谢您的分享，今日分享次数已达30条，休息一会儿明天再来哦~');
        }
    }
}
