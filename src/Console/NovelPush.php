<?php

namespace Haxibiao\Content\Console;

use App\Chapter;
use App\Novel;
use Illuminate\Console\Command;

class NovelPush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'novel:push {--id= : 开始ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'push本站的小说数据到哈希云';

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
        $qb = Novel::enabled();
        if ($start_id = $this->option('id')) {
            $this->info("跳过 id:{$start_id} 之前的小说内容");
            $qb = $qb->where('id', '>', $start_id);
        }

        $this->info("开始上传novel数据");
        $count = 0;
        $qb->chunkById(100, function ($novels) use (&$count) {
            foreach ($novels as $novel) {
                $this->info("正在上传小说" . $novel->name);
                //只处理纯文章，视频article不处理
                $exists = \DB::connection('media')
                    ->table('novels')
                    ->where('name', $novel->name)
                    ->exists();
                // 不存在则存储
                if (!$exists) {
                    $novelSaveResult = \DB::connection('media')->table('novels')->insert([
                        'name'          => $novel->name,
                        'source'        => config('app.name_cn'),
                        'introduction'  => $novel->introduction,
                        'cover'         => $novel->cover,
                        'author'        => $novel->author,
                        'words'         => $novel->words,
                        'status'        => $novel->status,
                        'count_user'    => $novel->count_user,
                        'count_chapter' => $novel->count_chapter,
                        'created_at'    => $novel->created_at,
                        'updated_at'    => $novel->updated_at,
                        'source'        => $novel->source,
                        'rank'          => $novel->rank,
                        'is_over'       => $novel->is_over,
                        'count_read'    => $novel->count_read,
                    ]);
                    $chapters = Chapter::where('novel_id', $novel->id)->get();
                    foreach ($chapters as $chapter) {
                        $chapterSaveResult = \DB::connection('media')->table('chapters')->insert([
                            'novel_id'   => $novelSaveResult->id,
                            'no'         => $chapter->no,
                            'title'      => $chapter->title,
                            'content'    => $chapter->content,
                            'created_at' => $chapter->created_at,
                            'updated_at' => $chapter->updated_at,
                        ]);
                        $this->info(" $chapterSaveResult->no 章节, $chapterSaveResult->title 已保存!");
                    }
                    $count++;
                    $this->info("小说上传成功：" . $novelSaveResult->name);
                } else {
                    $this->warn("小说已存在，跳过：" . $novel->name);
                }
            }
        });
        $this->info("上传成功" . $count . "条小说数据");
    }
}
