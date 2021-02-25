<?php

namespace Haxibiao\Content\Console\Cms;

use Haxibiao\Content\Article;
use Haxibiao\Media\Movie;
use Haxibiao\Content\Site;
use Haxibiao\Media\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SeoWorker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seo:work {--domain= : 指定操作站点域名} {--submit_count= : 自动提交收录数量,必须是100的倍数} {--sync_count= : 每日sync数据条数} {--type= : 推送类型movie\video\article}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'seo work，自动收录，自动同步数据';

    //百度api推送参数
    public $api;
    public $submit_count;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->submit_count = $this->option('submit_count') ?? 3000; //每日提交收录数量，默认3000,必须是100的倍数
        $this->sync_count   = $this->option('sync_count') ?? null; //每日同步数据数量 默认5 暂支持article

        //都使用爱你城的article数据吧，目前内涵云上article只有懂代码和爱你城...后面其他站点添加内容进来再加这个option
        // if ($sync_count = $this->option('sync_count') ?? null) {
        //     Artisan::call("article:sync", [
        //         "--domain" => "ainicheng.com",
        //         "--num"    => $sync_count,
        //     ]);
        // }

        $qb = Site::whereNotNull('ziyuan_token');
        if ($domain = $this->option('domain') ?? null) {
            $qb->where('domain', $domain);
        }

        $qb->chunkById(100, function ($sites) {
            foreach ($sites as $site) {
                //清空昨天的data数据
                $site->update(['data' => null]);
                if ($site->domain && $site->ziyuan_token) {
                    //各个站点提交的百度api
                    $this->api = "http://data.zz.baidu.com/urls?site=" . $site->domain . '&token=' . $site->ziyuan_token;
                    //提交收录
                    $this->pushUrls($site);
                }
            }
        });
    }

    public function pushUrls($site)
    {
        $domain = $site->domain;
        //从前往后优先推送这几个模块的内容,后面需要推送question、post等都可以加在这
        $contents = ["movie" => Movie::class, "article" => Article::class, "video" => Video::class];
        $type     = $this->option('type') ?? null;
        if ($type && $contents[$type]) {
            $contents = [$type => $contents[$type]];
        }

        //当前推送了多少url
        $push_count = 0;

        foreach ($contents as $key => $value) {
            //获取上次最后提交的id
            $cache_key    = "seo_" . $domain . "_" . $key;
            $last_time_id = Cache::get($cache_key) ?? 1;

            $value::query()->where('id', '>=', $last_time_id)
                ->chunkById(100, function ($items) use ($key, $domain, &$push_count, $cache_key, $site) {
                    //整理要推送的url数组（100一组）
                    foreach ($items as $item) {
                        $urls[]      = $domain . "/{$key}/" . $item->id;
                        $cache_value = $item->id; //记录最后一个id
                    }
                    Cache::put($cache_key, $cache_value);

                    //发送
                    $result = pushSeoUrl($urls, $this->api);
                    if (str_contains($result, "success")) {
                        $result = json_decode($result);
                        $push_count += 100;

                        //记录推送数据
                        $data                  = $site->data ?? [];
                        $data['baidu_remain']  = $result->remain;
                        $data['baidu_success'] = ($data['baidu_success'] ?? 0) + $result->success;
                        $site->data            = $data;
                        $site->save();
                        Log::info($domain . "剩余可推送URL条数:" . $result->remain);
                    } else {
                        Log::info($domain . "推送失败,退出");
                        return false;
                    }

                    //达到数量，退出
                    if ($push_count >= $this->submit_count) {
                        Log::info($domain . "推送完成,退出");
                        return false;
                    }
                });
        }
    }
}
