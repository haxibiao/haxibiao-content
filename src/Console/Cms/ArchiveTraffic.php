<?php

namespace Haxibiao\Content\Console\Cms;

use App\Dimension;
use App\Site;
use App\Traffic;
use Illuminate\Console\Command;

class ArchiveTraffic extends Command
{

    protected $signature   = 'archive:traffic';
    protected $description = '每天归档seo流量 日报数据 抓取，提交，索引，搜索来路 ....';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // //归档每个站每天seo流量抓取，搜索来路
        Traffic::where('created_at', '<=', today()->subday(3)->toDateString())->chunk(1000, function ($traffics) {
            foreach ($traffics as $traffic) {
                if ($traffic->bot) {
                    $this->track(
                        $traffic->bot . '爬取数',
                        1,
                        $this->getDomainName($traffic->domain) ?? $traffic->domain,
                        $traffic->created_at->toDateString()
                    );
                }
                if ($traffic->engine) {
                    $this->track(
                        $traffic->engine . '搜索量',
                        1,
                        $this->getDomainName($traffic->domain) ?? $traffic->domain,
                        $traffic->created_at->toDateString()
                    );
                }
            }
        });
        //清理3天前的流量记录
        Traffic::where('created_at', '<=', today()->subday(3)->toDateString())->delete();

        //保存每个站近30天的百度索引量
        $sites = Site::get();
        foreach ($sites as $site) {
            $this->info($site->domain . " 正在检查索引量...");
            $json = $site->json;
            if ($json) {
                $include = $json['baidu'];
                unset($include[today()->subday(30)->toDateString()]);
                $include[today()->toDateString()] = baidu_include_check($site->domain)[0]['收录'];
                $json['baidu']                    = $include;
                $site->json                       = $json;
            } else {
                $include = [];
                for ($i = 29; $i > 0; $i--) {
                    $include[today()->subday($i)->toDateString()] = 0;
                }
                //
                $include[today()->toDateString()] = baidu_include_check($site->domain)[0]['收录'];
                $json['baidu']                    = $include;
                $site->json                       = $json;
            }
            $site->save();
        }

        //归档活跃域名近三天百度索引量
        for ($i = 2; $i >= 0; $i--) {
            foreach (Site::active()->get() as $site) {
                $domain    = $site->domain;
                $data      = $site->json;
                $value     = $data['baidu'][today()->subday($i)->toDateString()];
                $dimension = Dimension::firstOrNew([
                    'date'  => today()->subday($i)->toDateString(),
                    'group' => $domain,
                    'name'  => '百度索引量',
                ]);
                $dimension->value = $value;
                $dimension->save();
            }
        }
    }

    public function track($name, $value, $group, $date)
    {
        $dimension = Dimension::whereGroup($group)
            ->whereName($name)
            ->where('date', $date)
            ->first();
        if (!$dimension) {
            $dimension = Dimension::create([
                'date'  => $date,
                'group' => $group,
                'name'  => $name,
                'value' => $value,
            ]);
        } else {
            //更新数值和统计次数
            $dimension->value = $dimension->value + $value;
            $dimension->save();
        }
    }

    public function getDomainName($domain)
    {
        $names = config('seo.sites') ?? [];
        return array_get($names, $domain);
    }
}
