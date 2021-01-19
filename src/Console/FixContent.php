<?php

namespace Haxibiao\Content\Console;

use Haxibiao\Breeze\User;
use Haxibiao\Content\Post;
use Haxibiao\Media\Spider;
use Haxibiao\Media\Video;
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

    public function videos()
    {
        $this->info("把video sync过来配文 封面 和播放地址正常的视频发布成动态到马甲号下");

        $qb = Video::orderBy('id');
        //新同步过来的未发布的
        $qb = $qb->where('id', '>', Post::max('video_id') ?? 0);

        $qb->chunk(100, function ($videos) {
            foreach ($videos as $video) {

                $post = Post::firstOrNew([
                    'video_id' => $video->id,
                ]);

                if ($post->id) {
                    continue;
                }

                //随机一个编辑账户做马甲
                $editor = User::role(User::EDITOR_STATUS)->inRandomOrder()->first();

                //同步对应的post
                $review_id  = Post::makeNewReviewId();
                $review_day = Post::makeNewReviewDay();
                $postFields = [
                    'user_id'     => $editor ? $editor->id : 1,
                    'content'     => $video->title,
                    'description' => $video->title,
                    'video_id'    => $video->id,
                    'review_id'   => $review_id,
                    'review_day'  => $review_day,
                    'status'      => 1,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
                $post->forceFill(
                    $postFields
                )->saveDataOnly();

                $this->info("发布动态 $post->id $post->video_id $post->content $post->cover");
            }
        });

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
