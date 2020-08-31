<?php

namespace Haxibiao\Content\Console;

use App\Article;
use Haxibiao\Content\Post;
use Haxibiao\Media\Image;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

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

                $count++;
            }
        });
        $this->info('成功修复动态' . $count . '个');
        $endTime = microtime(true);
        $time    = $endTime - $startTime;
        $this->info('本次修复数据,总耗时:' . $time);

    }

    private function insertPost($article){

        $dispatcher = Post::getEventDispatcher();
        Post::unsetEventDispatcher();

        // 注意参数判空
        $post = new Post();
        $post->video_id      = $article->video_id;
        $post->description   = $article->description;
        $post->content       = $article->body;
        $post->status        = $article->status;
        $post->hot           = $article->is_hot;
        if($article->review_id){
            $post->review_id     = $article->review_id;
            $post->review_day    = substr($article->review_id,0,8);
        }
        $post->updated_at    = $article->updated_at;
        $post->created_at    = $article->created_at;
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
        $post->categories()->sync($syncData);
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
        if($article->cover_path){
            // 处理图片关系
            $fullUrl = Storage::disk('cosv5')->url($article->cover_path);
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
        DB::table('likes')->where('liked_id',$article->id)
            ->where('liked_type','articles')
            ->update([
                'liked_type' => 'posts',
                'liked_id'   => $post->id
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
}
