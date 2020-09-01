<?php

namespace Haxibiao\Content\Console;

use App\Article;
use Haxibiao\Content\Post;
use Haxibiao\Media\Image;
use Haxibiao\Media\Spider;
use Haxibiao\Media\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostReFactoringCommand extends Command
{
    protected $signature = 'haxibiao:post:refactoring';

    protected $description = '修复Post数据,可重复执行';

    public function handle()
    {
        $startTime = microtime(true);
        $count     = 0;
        Article::withTrashed()->whereIn('type',['video','post'])->chunk(100, function ($articles) use (&$count) {
            foreach ($articles as $article) {
                $this->info('正在修复：'. $article->id);
                $this->info('正在修复：'. $article->review_id);
                // Post填充Article中的动态
                $newPost = $this->insertPost($article);

                // 修复分类关系
                $this->handleCategory($article, $newPost);

                // 修复图片关系,限图文动态
                $this->handleImage($article, $newPost);

                // 修复评论
                $this->handleComment($article, $newPost);

                // 修复点赞
                $this->handleLike($article, $newPost);

                // 修复贡献值
                $this->handleContribute($article, $newPost);

                // 修复浏览历史
                $this->handleVisit($article, $newPost);

                // 修复操作日志
                $this->handleAction($article, $newPost);

                // 修复标签
                $this->handleTag($article, $newPost);

                // 修复spider
                $this->handleSpider($article, $newPost);

                $count++;
            }
        });
        $this->info('成功修复动态' . $count . '个');
        $endTime = microtime(true);
        $time    = $endTime - $startTime;
        $this->info('本次修复数据,总耗时:' . $time);

        // 修复冗余数据
        Post::withTrashed()->with(['likes','comments'])->chunk(100,function($posts){
            foreach ($posts as $post){
                DB::table('posts')->where('id',$post->id)->update([
                    'count_likes'    => $post->likes()->count(),
                    'count_comments' => $post->comments()->count()
                ]);
                $this->info($post->id);
            }
        });
    }

    private function insertPost($article){

        $dispatcher = Post::getEventDispatcher();
        Post::unsetEventDispatcher();

        // 注意参数判空
        $post = Post::firstOrNew([
            'user_id'   => $article->user_id,
            'created_at'=> $article->created_at
        ]);
        $post->status        = $article->status;
        $post->video_id      = $article->video_id;
        $post->description   = $article->description;
        $post->content       = $article->body;
        $post->hot           = $article->is_hot;
        if($article->review_id){
            $post->review_id     = $article->review_id;
            $post->review_day    = substr($article->review_id,0,8);
        }
        $post->updated_at    = $article->updated_at;
        $post->deleted_at    = $article->deleted_at;
        $post->save(['timestamps'=>false]);

        Post::setEventDispatcher($dispatcher);

        return $post;
    }

    private function handleCategory($article,$post){

        if (!Schema::hasTable('article_category')) {
            return;
        }

        $syncData = [];
        $categories = DB::table('article_category')->where('article_id',$article->id)
            ->get();
        foreach ($categories as $category){
            $syncData[$category->id] = [
                'created_at' => $category->created_at,
                'updated_at' => $category->updated_at,
            ];
        }
        $mainCategoryId = $article->category_id;
        if(!key_exists($mainCategoryId,$syncData)){
            $syncData[$mainCategoryId] = [
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        $post->categorize($syncData);
    }

    private function handleImage($article,$post){

        // 跳过视频动态
        if($article->video_id){
            return;
        }

        $syncData = [];
        $images = DB::table('article_image')->where('article_id',$article->id)
            ->get();
        foreach ($images as $image){
            $syncData[$image->id] = [
                'created_at' => $image->created_at,
                'updated_at' => $image->updated_at,
            ];
        }
        // 主封面图
        if($article->cover_path && filter_var($article->cover_path, FILTER_VALIDATE_URL)){
            $fullUrl = str_replace('https','http',$article->cover_path);
            if(!str_contains($article->cover_path,'http')){
                $fullUrl = Storage::disk('cosv5')->url($fullUrl);
            }
            // 处理图片关系
            try{
                $hash = hash_file('md5', $fullUrl);
                list($width, $height) = getimagesize($fullUrl);
                $image = Image::firstOrCreate([
                    'hash' => $hash
                ],[
                    'user_id' => 1,
                    'path'    => $article->cover_path,
                    'disk'   => 'cosv5',
                    'extension' => pathinfo($article->cover_path,PATHINFO_EXTENSION),
                    'width'     => $width,
                    'height'    => $height
                ]);
                $syncData[$image->id] = [
                    'created_at' => $image->created_at,
                    'updated_at' => $image->updated_at,
                ];
            } catch (\Exception $ex){

            }
        }
        $post->images()->sync($syncData);
    }

    private function handleComment($article,$post){

        if (!Schema::hasTable('comments')) {
            return;
        }
        DB::table('comments')->where('commentable_id',$article->id)
            ->where('commentable_type','articles')
            ->update([
                'commentable_type' => 'posts',
                'commentable_id'   => $post->id
            ]);
    }

    private function handleLike($article,$post){

        if (!Schema::hasTable('likes')) {
            return;
        }
        $columnOfTypeName  = 'likable_type';
        $columnOfIdName    = 'likable_id';
        if( !Schema::hasColumn('likes','likable_id')){
            $columnOfTypeName = 'liked_type';
            $columnOfIdName   = 'liked_id';
        }

        DB::table('likes')->where($columnOfIdName,$article->id)
            ->where($columnOfTypeName,'articles')
            ->update([
                $columnOfTypeName => 'posts',
                $columnOfIdName   => $post->id
            ]);
    }

    private function handleContribute($article,$post){

        if (!Schema::hasTable('contributes')) {
            return;
        }
        DB::table('contributes')->where('contributed_id',$article->id)
            ->where('contributed_type','articles')
            ->update([
                'contributed_type' => 'posts',
                'contributed_id'   => $post->id
            ]);
    }

    private function handleVisit($article,$post){

        if (!Schema::hasTable('visits')) {
            return;
        }
        DB::table('visits')->where('visited_id',$article->id)
            ->where('visited_type','articles')
            ->update([
                'visited_type' => 'posts',
                'visited_id'   => $post->id
            ]);
    }

    private function handleAction($article,$post){

        if (!Schema::hasTable('actions')) {
            return;
        }
        DB::table('actions')->where('actionable_id',$article->id)
            ->where('actionable_type','articles')
            ->update([
                'actionable_type' => 'posts',
                'actionable_id'   => $post->id
            ]);
    }

    private function handleTag($article,$post){

        if (!Schema::hasTable('taggables')) {
            return;
        }
        DB::table('taggables')->where('taggable_id',$article->id)
            ->where('taggable_type','articles')
            ->update([
                'taggable_type' => 'posts',
                'taggable_id'   => $post->id
            ]);
    }

    private function handleSpider($article,$post){

        $isDouYinSpider = Str::contains($article->source_url, ['v.douyin.com', 'www.iesdouyin.com']);

        if(!$isDouYinSpider){
            return;
        }
        $video = Video::withTrashed()
            ->where('id',$article->video_id)
            ->first();
        if(!$video){
            return;
        }

        $dispatcher = Spider::getEventDispatcher();
        Spider::unsetEventDispatcher();

        $spider = Spider::firstOrNew([
            'source_url' => $article->source_url,
        ]);

        $spider->user_id = $article->user_id;
        $spider->status  = $video->status >= 1;// 大于1代表正常状态
        $spider->spider_id   = $video->id;
        $spider->spider_type = 'videos';
        $spider->data = [
            'title' => $article->description
        ];
        $spider->created_at  = $video->created_at;
        $spider->updated_at  = $video->updated_at;
        $spider->save(['timestamps' => false]);

        Spider::setEventDispatcher($dispatcher);

        // DB操作不触发模型事件
        DB::table('posts')->where('id',$post->id)
            ->update([
                'spider_id' => $spider->id,
            ]);
    }
}
