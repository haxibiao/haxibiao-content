<?php

namespace Haxibiao\Content\Console;

use Haxibiao\Content\Categorizable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RefactorCategorizable extends Command
{
    protected $signature = 'refactor:categorizable';

    protected $description = '重新构建categorizable,可重复执行';

    public function handle()
    {
        // article_category
        if (Schema::hasTable('article_category')) {
            $this->comment('start Fix article_category');
            $articleCategories = DB::table('article_category')->get();
            foreach ($articleCategories as $articleCategory) {
                $categorizable = Categorizable::firstOrNew([
                    'categorizable_id'   => $articleCategory->article_id,
                    'categorizable_type' => 'articles',
                    'category_id'        => $articleCategory->category_id,
                ]);
                $categorizable->created_at = $articleCategory->created_at;
                $categorizable->updated_at = $articleCategory->updated_at;
                $categorizable->submit     = $articleCategory->submit;
                $categorizable->save(['timestamps' => false]);
            }
            $this->comment('end Fix article_category');
        }

        // category_issue
        if (Schema::hasTable('category_issue')) {
            $this->comment('start Fix category_issue');
            $categoryIssues = DB::table('category_issue')->get();
            foreach ($categoryIssues as $categoryIssue) {
                $categorizable = Categorizable::firstOrNew([
                    'categorizable_id'   => $categoryIssue->issue_id,
                    'categorizable_type' => 'issues',
                    'category_id'        => $categoryIssue->category_id,
                ]);
                $categorizable->created_at = $categoryIssue->created_at;
                $categorizable->updated_at = $categoryIssue->updated_at;
                $categorizable->save(['timestamps' => false]);
            }
            $this->comment('end Fix category_issue');
        }

        // category_question
        if (Schema::hasTable('category_question')) {
            $this->comment('start Fix category_question');
            $categoryQuestions = DB::table('category_question')->get();
            if ($categoryQuestions) {
                foreach ($categoryQuestions as $categoryQuestion) {
                    $categorizable = Categorizable::firstOrNew([
                        'categorizable_id'   => $categoryQuestion->question_id,
                        'categorizable_type' => 'questions',
                        'category_id'        => $categoryQuestion->category_id,
                    ]);
                    $categorizable->created_at = $categoryQuestion->created_at;
                    $categorizable->updated_at = $categoryQuestion->updated_at;
                    $categorizable->save(['timestamps' => false]);
                }
                $this->comment('end Fix category_question');
            }
        }

        //category_video
        if (Schema::hasTable('category_video')) {
            $this->comment('start Fix category_video');
            $categoryVideos = DB::table('category_video')->get();
            foreach ($categoryVideos as $categoryVideo) {
                $categorizable = Categorizable::firstOrNew([
                    'categorizable_id'   => $categoryVideo->video_id,
                    'categorizable_type' => 'videos',
                    'category_id'        => $categoryVideo->category_id,
                ]);
                $categorizable->created_at = $categoryVideo->created_at;
                $categorizable->updated_at = $categoryVideo->updated_at;
                $categorizable->save(['timestamps' => false]);
            }
            $this->comment('end Fix category_video');
        }
    }

}
