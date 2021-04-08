<?php

namespace Haxibiao\Content\Console;

use App\Comment;
use App\Movie;
use App\User;
use Illuminate\Console\Command;

class SyncDouBanComments extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:comments {origin?} ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步豆瓣影视评论';

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
        $origin = $this->argument('origin');

        $this->info("start  SyncDouBanComments");

        $this->importComment($origin);

        $this->info("finish SyncDouBanComments");

        return 1;
    }

    public function importComment($origin = 'neihandianying')
    {
        $origin      = is_null($origin) ? 'neihandianying' : $origin;
        $vestUserIds = User::where('role_id', User::VEST_STATUS)
            ->inRandomOrder()->pluck('id')->toArray();
        Comment::on($origin)
            ->where('commentable_type', "movies")
            ->chunkById(100, function ($comments) use ($origin, $vestUserIds) {
                $this->info('import SyncDouBanComments processing, from:' . $origin);
                foreach ($comments as $sourceComments) {
                    try {
                        $user_id = array_random(array_values($vestUserIds));

                        //同步相关的movie
                        $sourceMovie = Movie::on($origin)->find($sourceComments->commentable_id);
                        if (!isset($sourceMovie)) {
                            $this->warn("原电影数据不存在，跳过");
                            continue;
                        }

                        $movie = Movie::where([
                            'name'       => $sourceMovie->name,
                            'source_key' => $sourceMovie->id,
                        ])->first();
                        if (!isset($movie)) {
                            $this->warn("未曾导入过的电影，跳过");
                            continue;
                        }
                        $this->info("保存movie->id:" . $movie->id . ' ' . $movie->name . '成功');

                        //同步相关comments
                        $comment = Comment::updateOrCreate([
                            'commentable_type' => 'movies',
                            'commentable_id'   => $movie->id,
                            'body'             => $sourceComments->content,
                        ], [
                            'user_id'    => $user_id,
                            'created_at' => $sourceComments->created_at,
                        ]);
                        $this->info("comments->id:" . $comment->id . ' ' . $comment->body . '成功');

                    } catch (\Exception $ex) {
                        $info = $ex->getMessage();
                        $this->info("异常信息" . $info);
                    }

                }

            });

    }
}
