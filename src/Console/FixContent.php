<?php

namespace Haxibiao\Content\Console;

use Haxibiao\Breeze\User;
use Haxibiao\Content\Collection;
use Haxibiao\Content\Post;
use Haxibiao\Media\Spider;
use Haxibiao\Media\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixContent extends Command
{
    protected $signature = 'fix:content {table} {--force} {--start= : 开始位置id}';

    protected $description = '修复数据';

    public function handle()
    {
        if ($table = $this->argument('table')) {
            return $this->$table();
        }
        $this->error('请提供需要修复的table');
    }

    //修复文章(图解资源的body)
    public function fixBodys()
    {
        $this->info('修复图解body内容展示');
        DB::connection('media')->table('articles')->where('type', 'diagrams')->orderBy('id', 'asc')->chunk(1000, function ($articles) {
            $this->info('开始处理body数据....');
            $count = 0;
            foreach ($articles as $article) {
                $dataInfos = $article->body;
                $body      = '<div>';

                //匹配到所有的图片信息
                preg_match_all('/<img.*?src=[\"|\']?(.*?)[\"|\']?\s.*?>/i', $dataInfos, $image);
                //匹配所有的描述
                preg_match_all('/<figcaption[^>]*([\s\S]*?)<\/figcaption>/i', $dataInfos, $description);

                //构造json内容
                $images       = $image[1];
                $descriptions = $description[1];
                $json         = [];
                for ($i = 0; $i < count($images) && $i < count($descriptions); $i++) {
                    $jsonInfo['image']       = str_replace('http', 'https', $images[$i]);
                    $jsonInfo['description'] = str_replace('>', '', $descriptions[$i]);
                    $json[$i]                = $jsonInfo;
                }

                //修复body内容数据
                foreach ($json as $info) {
                    $href    = data_get($info, 'image');
                    $content = data_get($info, 'description');
                    $body .= "<p><img alt='$content' src='$href' width='960' height='540'/></p><p style='text-align:justify'>$content</p>";
                }

                $body .= '</div>';
                DB::connection('media')->table('articles')->where('id', $article->id)->update([
                    'body' => $body,
                    'json' => $json,
                    'type' => 'diagrams',
                ]);
                $count++;
                $this->info('修改body && json 成功' . $article->title . 'id为:' . $article->id);
            }
            echo "\n上传成功" . $count . "条文章数据";
        });
    }

    public function videos()
    {
        $this->info("把video sync过来配文 封面 和播放地址正常的视频发布成动态到马甲号下");

        $qb = Video::orderBy('id');

        //新同步过来的未发布的
        if (!$this->option('force')) {
            $qb = $qb->where('id', '>=', Post::max('video_id') ?? 0);
        }
        if ($start = $this->option('start')) {
            $qb = $qb->where('id', '>=', $start);
        }

        $qb->chunk(100, function ($videos) {
            foreach ($videos as $video) {
                $this->info("视频 $video->id $video->title $video->cover");

                $post = \Haxibiao\Content\Post::firstOrNew([
                    'video_id' => $video->id,
                ]);

                if ($post->id && !$this->option('force')) {
                    continue;
                }

                //随机一个编辑账户做马甲
                $editor = User::role(User::EDITOR_STATUS)->inRandomOrder()->first();

                //同步对应的post

                $postFields = [
                    'user_id'     => $editor ? $editor->id : 1, //马甲编辑用户
                    'content'     => $video->title,
                    'description' => $video->title,
                    'video_id'    => $video->id,

                    'status'      => 1,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
                $post->forceFill($postFields);
                $post->save();

                //合集
                if ($video->collection_key) {
                    $collection = Collection::firstOrNew([
                        'collection_key' => $video->collection_key,
                    ]);
                    $collection->user_id = $editor ? $editor->id : 1; //马甲编辑用户
                    $collection->name    = $video->collection;
                    $collection->logo    = $video->cover_url;
                    $collection->save();

                    //合集收录视频动态
                    $collection->collect([$post->id], 'posts');
                    $this->comment(" - 合集 $collection->id $collection->name  收录成功 封面：$collection->logo");
                }

                $this->info("发布动态 $post->id $post->video_id $post->description $post->cover");
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
                    $this->info("$spider->id $post->id $post->description");
                    ++$count;
                }
            }
        });
        $this->info('成功修复数据:' . $count);
    }
}
