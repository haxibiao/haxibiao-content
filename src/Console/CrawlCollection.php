<?php

namespace Haxibiao\Content\Console;

use App\Collection;
use App\Exceptions\GQLException;
use App\Post;
use App\Spider;
use App\User;
use GuzzleHttp\Client;
use Haxibiao\Media\Jobs\MediaProcess;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CrawlCollection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawl:collection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '按用户主页合集爬取视频';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    const COLLECTIONS_URL = "https://aweme-lq.snssdk.com/aweme/v1/mix/list/?user_id=%s&cursor=%s&count=%s";
    const VIDEOS_URL      = "https://aweme-lq.snssdk.com/aweme/v1/mix/aweme/?mix_id=%s&cursor=%s&count=%s";

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //获取需要爬取的信息
        $sourceData = self::getJsonData('file/collections.json');
        foreach ($sourceData as $userPage) {
            $share_link = data_get($userPage, 'share_link');

            $collectionsName = data_get($userPage, 'collections');
            $userName        = data_get($userPage, 'vest_user');
            $this->info("start crawl user " . $share_link);
            self::processCrawl($share_link, $collectionsName, $userName);
        }

        return 0;
    }

    public static function processCrawl($share_link, $collectionsName, $userName)
    {

        //获取抖音用户id
        $user_id  = self::getDouYinUserId($share_link);
        $vestUser = User::where('name', $userName)->first();
        if (empty($vestUser)) {
            $vestUser = User::where('role_id', User::VEST_STATUS)->inRandomOrder()->first();
        }

        $hasMore     = true;
        $collections = [];
        $mixIds      = [];
        for ($cursor = 0, $count = 15; $hasMore;) {
            $crawlUrl = sprintf(self::COLLECTIONS_URL, $user_id, $cursor, $count);
            info($crawlUrl);
            //获取用户所有的合集信息
            $collectionData = self::getRequestData($crawlUrl);
            $hasMore        = (bool) data_get($collectionData, 'has_more', 0);
            $cursor         = data_get($collectionData, 'cursor', 0);
            $mixInfos       = data_get($collectionData, 'mix_infos', null);
            if (empty($mixInfos)) {
                info('未找到该用户的合集!');
                continue;
            }

            //未指定爬取合集的情况，爬取该用户下的所有合集
            if (empty($collectionsName)) {
                foreach ($mixInfos as $mixInfo) {
                    info("开始爬取合集 " . data_get($mixInfo, 'mix_name'));
                    //创建对应的collection
                    $collection          = self::getMixInfoCollection($mixInfo, $vestUser);
                    $mixId               = data_get($mixInfo, 'mix_id');
                    $mixIds[]            = $mixId;
                    $collections[$mixId] = $collection;
                }

            } else {
                //爬取指定的合集
                foreach ($mixInfos as $mixInfo) {
                    $name = data_get($mixInfo, 'mix_name');
                    if (in_array($name, $collectionsName)) {
                        info("开始爬取合集 " . $name);
                        //创建对应的collection
                        $collection          = self::getMixInfoCollection($mixInfo, $vestUser);
                        $mixId               = data_get($mixInfo, 'mix_id');
                        $mixIds[]            = $mixId;
                        $collections[$mixId] = $collection;
                    } else {
                        continue;
                    }

                }
            }

        }

        //爬取每个合集下的视频
        foreach ($mixIds as $mixId) {
            $hasMore = true;
            $postIds = [];
            for ($cursor = 0, $count = 15; $hasMore;) {
                $crawlUrl = sprintf(self::VIDEOS_URL, $mixId, $cursor, $count);
                info($crawlUrl);
                $videoData = self::getRequestData($crawlUrl);
                $hasMore   = (bool) data_get($videoData, 'has_more', 0);
                $cursor    = data_get($videoData, 'cursor', 0);

                $videos = data_get($videoData, 'aweme_list');

                foreach ($videos as $video) {
                    info("开始爬取视频 " . data_get($video, 'desc'));
                    $created             = date('Y-m-d H:i:s', data_get($video, 'create_time'));
                    $shareUrl            = data_get($video, 'share_info.share_url');
                    $spider              = Spider::has('video')->firstOrNew(['source_url' => $shareUrl]);
                    $spider->user_id     = $vestUser->id;
                    $spider->spider_type = 'videos';
                    $spider->saveDataOnly();
                    //创建对应的动态
                    $post              = Post::firstOrNew(['spider_id' => $spider->id]);
                    $post->status      = Post::PRIVARY_STATUS;
                    $post->created_at  = $created;
                    $post->user_id     = $vestUser->id;
                    $shareTitle        = data_get($video, 'share_info.share_title');
                    $post->description = str_replace(['#在抖音，记录美好生活#', '@抖音小助手', '抖音', 'dou', 'Dou', 'DOU', '抖音助手'], '', $shareTitle);

                    $reviewDay        = $post->created_at->format('Ymd');
                    $reviewId         = Post::makeNewReviewId($reviewDay);
                    $post->review_id  = $reviewId;
                    $post->review_day = $reviewDay;
                    $post->save();
                    //将视频归入合集中
                    $postIds[$post->id] = ['sort_rank' => data_get($video, 'mix_info.statis.current_episode')];

                    //登录
                    Auth::login($vestUser);
                    try {
                        //爬取对应的数据
                        dispatch(new MediaProcess($spider->id));
                    } catch (\Exception $ex) {
                        $info = $ex->getMessage();
                        info("异常信息" . $info);
                    }

                }
            }

            //将视频归入合集中
            $collection = $collections[$mixId];
            $collection->posts()->sync($postIds);

            $collection->updateCountPosts();
            info($collection->name . "爬取完毕");
        }

    }

    //根据mix_info创建Collection
    public static function getMixInfoCollection($mixInfo, $vestUser)
    {
        $name        = data_get($mixInfo, 'mix_name');
        $description = data_get($mixInfo, 'desc');
        $logo        = data_get($mixInfo, 'cover_url.url_list.0');

        $cosPath = 'images/' . uniqid() . '.jpeg';
        Storage::cloud()->put($cosPath, file_get_contents($logo));
        $newImagePath = cdnurl($cosPath);
        $collection   = Collection::firstOrCreate(
            ['name' => $name, 'user_id' => $vestUser->id],
            [
                'description' => $description,
                'type'        => 'posts',
                'logo'        => $newImagePath,
                'status'      => Collection::STATUS_ONLINE,
                'json'        => [
                    'mix_info' => $mixInfo,
                ]]
        );

        return $collection;
    }

    //解析json数据
    public static function getJsonData($path = 'file/collections.json')
    {

        $completePath = cdnurl($path);
        // 从文件中读取数据到PHP变量
        $json_string = file_get_contents($completePath);

        // 用参数true把JSON字符串强制转成PHP数组
        $data = json_decode($json_string, true);
        return $data;

    }

    //获取抖音用户id
    public static function getDouYinUserId($url = '')
    {

        throw_if(is_null($url), GQLException::class, "主页链接为空");

        $url = Spider::extractURL($url);

        $client   = new Client();
        $response = $client->request('GET', $url, [
            'http_errors'     => false,
            'allow_redirects' => false,
        ]);
        throw_if($response->getStatusCode() == 404, GQLException::class, '您分享的链接不存在,请稍后再试!');

        $referUrl = data_get($response->getHeader('location'), '0', '');
        throw_if(is_null($referUrl), GQLException::class, "获取抖音用户ID失败");

        $start = strripos($referUrl, "/");
        $end   = strpos($referUrl, "?");

        $user_id = substr($referUrl, $start + 1, $end - $start - 1);

        return $user_id;
    }

    /**
     *  获取链接数据
     * $url:需要请求的分页地址
     */

    public static function getRequestData($url = '')
    {
        $client   = new Client();
        $response = $client->request('GET', $url, [
            'http_errors' => false,
        ]);
        throw_if($response->getStatusCode() == 404, GQLException::class, '您分享的链接不存在,请稍后再试!');

        $contents = $response->getBody()->getContents();
        throw_if(empty($contents), GQLException::class, '获取内容链接失败!');

        $contents = json_decode($contents, true);
        return $contents;

    }
}
