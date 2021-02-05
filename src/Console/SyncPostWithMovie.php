<?php

namespace Haxibiao\Content\Console;

use App\Movie;
use App\Post;
use App\User;
use App\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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
    protected $description = '导入答赚中已经绑定了movie的post';

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

    public function importCollect($origin = 'dazhuan')
    {
        $origin = is_null($origin) ? 'dazhuan' : $origin;

        Post::on($origin)
            ->publish()
            ->whereExists(function ($query) {
                return $query->from('postables')
                    ->whereRaw('posts.id = postables.post_id')
                    ->where('postable_type', 'movies')
                ;
            })
            ->chunkById(100, function ($posts) use ($origin) {
                $this->info("import SyncPostWithMovie processing");
                foreach ($posts as $sourcePost) {
                    try {
                    //同步video
                    $sourceVideo = $sourcePost->video;
                    $vestUser    = User::where('role_id', User::VEST_STATUS)->inRandomOrder()->first();

                    $video = Video::updateOrCreate([
                        'hash' => $sourceVideo->hash,
                    ], [
                        'user_id'      => $vestUser->id,
                        'title'        => $sourceVideo->filename,
                        'path'         => $sourceVideo->path,
                        'duration'     => $sourceVideo->json->duration,
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
                    $sourceMovieId = DB::connection($origin)->table('postables')
                        ->select('postable_id')
                        ->where('postable_type', 'movies')
                        ->where('post_id', $sourcePost->id)
                        ->first();
                    $sourceMovie = Movie::on($origin)->find($sourceMovieId->postable_id);
                    if(!isset($sourceMovie)){
                        continue;
                    }
                    $movie       = Movie::updateOrCreate([
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
                        'user_id'     => $vestUser->id,
                        'content'     => $sourcePost->content,
                        'description' => $sourcePost->description,
                        'review_id'   => $review_id,
                        'review_day'  => $review_day,
                        'status'      => Post::PUBLISH_STATUS,
                    ]);
                    $this->info("保存post->id:" . $post->id . ' ' . $post->content . '成功');

                    //关联post with movie
                    $post->toggleLink([$movie->id], $post->id, 'posts');
                    $this->info("post关联movie成功:" . $post->id . ' ' . $movie->id . '成功');
                    }catch(\Exception $ex){
                        info($ex);
                    }
            

                }

            });

    }
}
