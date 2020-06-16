<?php

namespace haxibiao\content;

use haxibiao\media\Spider;
use Illuminate\Console\Command;

class FixContent extends Command
{
    protected $signature = 'fix:content {table}';

    protected $description = '修复数据';

    public function handle()
    {
        if ($table = $this->argument('table')) {
            return $this->$table();
        }
        $this->error('请提供需要修复的table');

    }

    public function posts()
    {
        $count = 0;
        $qb    = Spider::latest('id');
        $this->info("总计需要修复抖音爬虫关联的动态数:" . $qb->count());
        $qb->chunk(100, function ($spiders) use (&$count) {
            foreach ($spiders as $spider) {
                $video_id = $spider->spider_id;
                $post     = \App\Post::where('video_id', $video_id)->first();
                if ($post) {
                    $post->spider_id = $spider->id;
                    $post->save();
                    $this->info("$spider->id $post->id $post->content");
                    ++$count;
                }
            }
        });
        $this->info('成功修复数据:' . $count);
    }
}
