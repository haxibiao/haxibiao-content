<?php

namespace Haxibiao\Content\Console\Cms;

use App\User;
use Carbon\Carbon;
use App\Site;
use Illuminate\Console\Command;

class CmsUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cms:update';

    const INTERVEL_HOUSE =  24;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每天为cms站群下的站点自动更新首页资源 专题 电影 视频 文章 动态 ....';

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
        //https://pm.haxifang.com/browse/HXB-29
        //自动给当前站视图关联的内容，更新时间最早的更新为当日凌晨更新，让首页内容每天更新
        //如果已有24小时内手动更新的，跳过
        //自动优先更新当前站点专注的专题下的内容
        //站点管理下，允许站长设置当前站点专注的专题(电影，视频，文章，动态)

        $sites = Site::with('related')->get();
        foreach ($sites as $site) {
            self::handleSite($site);
        }
    }

    private function handleSite($site){

        $relatedList = data_get($site,'related',[]);
        foreach ($relatedList as $related){
            if(!$related){
                continue;
            }
            self::updateModel($related);

            // model is category
            if(data_get($related,'siteable_type',null) == 'categories'){
                $category = $related->siteable;
                $category = $category->load('related.categorizable');
                $relatedListOfCategory = data_get($category,'related');
                foreach ($relatedListOfCategory as $relatedOfCategory){
                    $categorizable = $relatedOfCategory->categorizable;
                    $siteable = $categorizable->siteable()
                        ->where('site_id',$site->id)
                        ->first();

                    // 该内容已经与当前网站关联，更新时间戳就行
                    if($siteable){
                        self::updateModel($siteable);
                        continue;
                    }
                    $categorizable->sites()->syncWithoutDetaching([
                        $site->id => [
                            'updated_at' => Carbon::today()
                        ]
                    ]);
                }
            }
        }
    }

    private function updateModel($model){
        if(Carbon::now()->diffInHours($model->updated_at) < self::INTERVEL_HOUSE){
            return;
        }
        $model->setUpdatedAt(Carbon::today());
    }
}
