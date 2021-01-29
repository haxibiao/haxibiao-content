<?php

namespace Haxibiao\Content\Console;

use App\Chapter;
use App\Novel;
use Illuminate\Console\Command;

class NovelSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'novel:sync {--id= : 开始ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步获取哈希云的小说数据';

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
        //记住最后同步的id
        $current_novel_id = 0;
        $qb               = \DB::connection('media')->table('novels');
        // 跳过已同步的小说
        if ($start_id = $this->option('id')) {
            $qb = $qb->where('id', '>', $start_id);
        }
        echo "开始同步小说\n";
        $count = 0;

        $qb->chunkById(100, function ($novels) use (&$count, &$current_novel_id) {
            foreach ($novels as $novel) {
                $this->info("同步小说:" . $novel->name);
                $current_novel_id = $novel->id;
                $result           = Novel::firstOrNew([
                    'name' => $novel->name,
                ], [
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
                // 不重复添加
                if (empty($result->id)) {
                    // 开始保存章节内容
                    $chapters = \DB::connection('media')->table('chapters')->where('novel_id', $novel->id)->get();
                    foreach ($chapters as $chapter) {
                        $chapterSaveResult = Chapter::create([
                            'novel_id'   => $novel->id,
                            'no'         => $chapter->no,
                            'title'      => $chapter->title,
                            'content'    => $chapter->content,
                            'created_at' => $chapter->created_at,
                            'updated_at' => $chapter->updated_at,
                        ]);
                        $this->info(" $chapterSaveResult->no 章节, $chapterSaveResult->title 保存成功!");
                    }
                    $result->save();
                    $this->info("$novel->name 小说保存成功!");
                    $count++;
                } else {
                    $this->warn("该小说已存在跳过:" . $novel->name);
                    continue;
                }

            }
        });

        $this->info("导入成功" . $count . "条小说数据");
        $this->info("最后导入的article ID为：" . $current_novel_id);
    }
}
