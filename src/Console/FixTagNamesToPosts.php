<?php

namespace Haxibiao\Content\Console;

use Haxibiao\Content\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * 优化Posts返回tag_names数组，提升gqls查询效率
 * https://pm.haxifang.com/browse/GC-204
 */
class FixTagNamesToPosts extends Command
{
    protected $signature = 'fix:tagNamesToPosts';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        if (!Schema::hasColumn('posts', 'tag_names'))
        {
            return;
        }
        Post::chunk(1000,function ($posts){
            foreach ($posts as $post){
                $post->tag_names =  implode(', ', $post->tagNames());
                $post->saveDataOnly();
                $this->info($post->id);
            }
        });
    }
}
