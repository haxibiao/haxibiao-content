<?php

namespace Haxibiao\Content\Console;

use App\Collection;
use App\User;
use Illuminate\Console\Command;

class SelectCollection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'select:collection';

    /**
     * The console command description.
     * 系统挑选推荐合集
     * 随机挑选不在置顶列表和推荐列表的合集
     * @var string
     */
    protected $description = '系统挑选推荐合集';

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
     * @return int
     */
    public function handle()
    {
        //不在推荐列表中的合集
        $qb = Collection::where(function ($query) {
            $query->whereNull('sort_rank')
                ->orWhere('sort_rank', 0);});

        //马甲号或者管理员创建的合集
        $qb = $qb->whereExists(function ($query) {
            $query->from('users')
                ->whereRaw('users.id = collections.user_id')
                ->whereIn('users.role_id', [User::VEST_STATUS, User::EDITOR_STATUS]);
        });
        //动态数量大于三的
        $qb = $qb->where('count_posts', '>=', 3);
        //过滤掉合集封面为默认封面的
        $qb = $qb->whereNotNull('logo')
            ->where('logo', '!=', config('haxibiao-content.collection_default_logo'));
        $collections = $qb->inRandomOrder()->take(6)->get();
        //如果推荐列表不够用了，就清空原推荐列表
        if ($qb->count()
        ) {
            info("重置推荐列表");

            Collection::where('sort_rank', '>=', Collection::RECOMMEND_COLLECTION)
                ->update(['sort_rank' => 0]);
        }

        foreach ($collections as $collection) {
            $collection->update(['sort_rank' => Collection::RECOMMEND_COLLECTION]);
        }
        info("更新推荐合集成功");

    }
}
