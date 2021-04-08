<?php

namespace Haxibiao\Content\Console;

use App\Movie;
use App\Post;
use App\User;
use App\Video;
use Illuminate\Console\Command;

class SyncPostWithMovie extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:postWithMovie {origin?} ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '从其他项目中已经绑定了movie的post';

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

        $this->info("start  SyncPostWithMovie");

        $this->importCollect($origin);

        $this->info("finish SyncPostWithMovie");

        return 1;
    }

    public function importCollect($origin = 'yingdaquan')
    {
        $origin      = is_null($origin) ? 'yingdaquan' : $origin;
        $vestUserIds = User::where('role_id', User::VEST_STATUS)
            ->inRandomOrder()->pluck('id')->toArray();
        Post::on($origin)
            ->publish()
            ->whereNotNull('movie_id')
            ->where('description', 'not like', "%说电影%")
        // 答赚中取数据
        // ->whereExists(function ($query) {
        //     return $query->from('postables')
        //         ->whereRaw('posts.id = postables.post_id')
        //         ->where('postable_type', 'movies')
        //     ;
        // })
            ->chunkById(100, function ($posts) use ($origin, $vestUserIds) {
                $this->info("import SyncPostWithMovie processing");
                foreach ($posts as $sourcePost) {
                    try {
                        $user_id = array_random(array_values($vestUserIds));

                        //同步video
                        $sourceVideo = $sourcePost->video;
                        //源数据部分hash值可能为空，
                        $video = Video::updateOrCreate([
                            'path' => $sourceVideo->path,
                        ], [
                            'user_id'      => $user_id,
                            'title'        => $sourceVideo->filename,
                            'duration'     => $sourceVideo->json->duration,
                            'hash'         => $sourceVideo->hash,
                            'cover'        => $sourceVideo->cover,
                            'status'       => $sourceVideo->status,
                            'json'         => $sourceVideo->json,
                            'disk'         => $sourceVideo->disk,
                            'qcvod_fileid' => $sourceVideo->fileid,
                            'created_at'   => now(),
                            'updated_at'   => now(),
                        ]
                        );
                        $this->info("保存video->id:" . $video->id . ' ' . $video->path . ' 成功');

                        //同步相关的movie
                        $sourceMovie = Movie::on($origin)->find($sourcePost->movie_id);
                        if (!isset($sourceMovie)) {
                            continue;
                        }
                        $movie = Movie::updateOrCreate([
                            'name' => $sourceMovie->name,
                        ], [
                            'introduction' => $sourceMovie->introduction,
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
                            'status'       => Movie::PUBLISH,
                        ]);
                        $this->info("保存movie->id:" . $movie->id . ' ' . $movie->name . '成功');

                        //同步相关post
                        $review_id  = Post::makeNewReviewId();
                        $review_day = Post::makeNewReviewDay();
                        $post       = Post::updateOrCreate([
                            'video_id' => $video->id,
                        ], [
                            'user_id'     => $user_id,
                            'content'     => $sourcePost->content,
                            'description' => $sourcePost->description,
                            'review_id'   => $review_id,
                            'review_day'  => $review_day,
                            'status'      => Post::PUBLISH_STATUS,
                        ]);
                        $this->info("保存post->id:" . $post->id . ' ' . $post->content . '成功');

                        //关联post with movie
                        $post->update(["movie_id" => $movie->id]);
                        $this->info("post关联movie成功:" . $post->id . ' ' . $movie->id . '成功');

                    } catch (\Exception $ex) {
                        $info = $ex->getMessage();
                        info("异常信息" . $info);
                    }
                }

            });

    }
}
