<?php

namespace Haxibiao\Content;

use Haxibiao\Content\Categorized;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CategoryReFactoringCommand extends Command
{
    protected $signature = 'haxibiao:category:refactoring';

    protected $description = '重新构建categoried,可重复执行';

    public function handle()
    {
        $this->callSilent('migrate');

        //article_category
        $this->comment('start Fix article_category');
        $articleCategories = DB::table('article_category')->get();
        foreach ($articleCategories as $articleCategory) {
            $categorized = Categorized::firstOrNew([
                'categorized_id'   => $articleCategory->article_id,
                'categorized_type' => 'articles',
                'category_id'      => $articleCategory->category_id,
            ]);
            $categorized->created_at = $articleCategory->created_at;
            $categorized->updated_at = $articleCategory->updated_at;
            $categorized->submit     = $articleCategory->submit;
            $categorized->save(['timestamps' => false]);
        }
        $this->comment('end Fix article_category');

        //category_issue
        $this->comment('start Fix category_issue');
        $categoryIssues = DB::table('category_issue')->get();
        foreach ($categoryIssues as $categoryIssue) {
            $categorized = Categorized::firstOrNew([
                'categorized_id'   => $categoryIssue->issue_id,
                'categorized_type' => 'issues',
                'category_id'      => $categoryIssue->category_id,
            ]);
            $categorized->created_at = $categoryIssue->created_at;
            $categorized->updated_at = $categoryIssue->updated_at;
            $categorized->save(['timestamps' => false]);
        }
        $this->comment('end Fix category_issue');

        //category_question
        $this->comment('start Fix category_question');
        $categoryQuestions = DB::table('category_question')->get();
        foreach ($categoryQuestions as $categoryQuestion) {
            $categorized = Categorized::firstOrNew([
                'categorized_id'   => $categoryQuestion->question_id,
                'categorized_type' => 'questions',
                'category_id'      => $categoryQuestion->category_id,
            ]);
            $categorized->created_at = $categoryQuestion->created_at;
            $categorized->updated_at = $categoryQuestion->updated_at;
            $categorized->save(['timestamps' => false]);
        }
        $this->comment('end Fix category_question');

        //category_video
        $this->comment('start Fix category_video');
        $categoryVideos = DB::table('category_video')->get();
        foreach ($categoryVideos as $categoryVideo) {
            $categorized = Categorized::firstOrNew([
                'categorized_id'   => $categoryVideo->video_id,
                'categorized_type' => 'videos',
                'category_id'      => $categoryVideo->category_id,
            ]);
            $categorized->created_at = $categoryVideo->created_at;
            $categorized->updated_at = $categoryVideo->updated_at;
            $categorized->save(['timestamps' => false]);
        }
        $this->comment('end Fix category_video');
    }

}
