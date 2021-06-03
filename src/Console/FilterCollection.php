<?php

namespace Haxibiao\Content\Console;

use Haxibiao\Content\Collection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FilterCollection extends Command
{

    /**
     * The name and signature of the Console command.
     *
     * @var string
     */
    protected $signature = 'filter:collection';

    /**
     * The Console command description.
     *
     * @var string
     */
    protected $description = '根据内涵云影库初步过滤国产内容合集';

    /**
     * Execute the Console command.
     *
     * @return void
     */
    public function handle()
    {
        DB::enableQueryLog();
        //获取内涵云国产文章
        $qbMediachain = DB::connection('mediachain')->table('movies');
        $movieNames1  = $qbMediachain->where('region', 'like', '%中国%')
            ->orWhere('region', 'like', '%台湾%')
            ->orWhere('region', 'like', '%香港%')
            ->pluck('name')->toArray();
        $movieNames2 = $qbMediachain->whereIn('country', ['中国', '台湾', '大陆', '香港'])
            ->pluck('name')->toArray();
        $movieNames = array_unique(array_merge($movieNames1, $movieNames2));

        //将未标识过的文章过滤一遍
        Collection::where('status', Collection::STATUS_UNSIGN)
            ->chunkById(50, function ($collections) use ($movieNames) {
                foreach ($collections as $collection) {
                    $collection->status = Collection::STATUS_SELECTED;
                    foreach ($movieNames as $movieName) {
                        if (str_contains($collection->name, $movieName) || str_contains($collection->description, $movieName)) {
                            $collection->status = Collection::STATUS_DOMESTIC;
                            $this->info("这篇合集被标记为国产版权内容" . $collection->id);
                            $this->info("匹配到的电影名为" . $movieName);

                        }
                    }
                    $collection->save();
                    $this->info("这篇合集已标记" . $collection->id);

                }
            });

        $this->info("合集筛查完毕");

    }
}
