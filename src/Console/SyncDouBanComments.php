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
                            continue;
                        }
                        $movie = Movie::updateOrCreate([
                            'name'       => $sourceMovie->name,
                            'source_key' => $sourceMovie->id,
                        ], [
                            'introduction' => $sourceMovie->introduction,
                            'user_id'      => $user_id,
                            'cover'        => $sourceMovie->cover,
                            'producer'     => $sourceMovie->producer,
                            'year'         => $sourceMovie->year,
                            'region'       => $sourceMovie->region,
                            'actors'       => $sourceMovie->actors,
                            'miner'        => $sourceMovie->miner,
                            'count_series' => $sourceMovie->count_series,
                            'rank'         => $sourceMovie->rank,
                            'country'      => $sourceMovie->country,
                            'subname'      => $sourceMovie->subname,
                            'score'        => $sourceMovie->score,
                            'tags'         => $sourceMovie->tags,
                            'hits'         => $sourceMovie->hits,
                            'lang'         => $sourceMovie->lang,
                            'data'         => json_encode($sourceMovie->data),
                            'data_source'  => json_encode($sourceMovie->data_source),
                            'status'       => Movie::PUBLISH,
                        ]);
                        $this->info("保存movie->id:" . $movie->id . ' ' . $movie->name . '成功');

                        //同步相关comments
                        $comment = Comment::updateOrCreate([
                            'commentable_type' => 'movies',
                            'commentable_id'   => $movie->id,
                        ], [
                            'user_id'    => $user_id,
                            'body'       => $sourceComments->content,
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
