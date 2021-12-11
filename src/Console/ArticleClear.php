<?php

namespace Haxibiao\Content\Console;

use App\Article;
use Illuminate\Console\Command;

class ArticleClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'article:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        if (!config('content.enable.haxiyun')) {
            dd("please enable haxiyun on .env");
        }
        $qb = \DB::connection('media')->table('articles')->where('source', config('app.domain'))->whereNotNull('source_id');
        $qb->chunkById(1000, function ($articles) {
            $articleID = $articles->pluck('source_id');
            Article::whereIn('id', $articleID)->update(['body' => null]);
            $this->info("已清空1000个文章的body...");
        });
    }
}
