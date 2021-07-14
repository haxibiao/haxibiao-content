<?php

namespace Haxibiao\Content\Traits;

use Haxibiao\Content\Category;
use Haxibiao\Question\CategoryUser;
use Haxibiao\Question\Question;
use Illuminate\Support\Facades\DB;

trait CategoryScopes
{
    public function scopeOfKeyword($query, $keyword)
    {
        $jieba = app('jieba');
        //失败时，就默认不切词
        $words[] = $keyword;
        try {
            $words = $jieba->cutForSearch($keyword);
        } catch (\Exception $ex) {
            //失败时,不需要处理，没意义
        }

        //一个词也必须是数组
        foreach ($words as $word) {
            $query->where('name', 'like', "%{$word}%")->orWhere('description', 'like', "%{$word}%");
        }

        return $query;
    }

    public function scopePublished($query)
    {
        return $query->where('status', Category::PUBLISH);
    }

    public function scopeUnofficial($query)
    {
        return $query->where('is_official', 0);
    }

    public function scopeAllowSubmit($query)
    {
        return $query->where('allow_submit', Category::ALLOW_SUBMIT);
    }

    public function scopeSkipParent($query)
    {
        return $query->whereNull("parent_id");
    }

    public function scopeArctileType($query)
    {
        return $query->where('type', Category::ARTICLE_TYPE_ENUM);
    }

    public function scopeQuestionType($query)
    {
        return $this->ofQuestion($query);
    }

    public function scopePublishedQuestionTypeAndAllowSubmit($query)
    {
        return $query->published()->questionType()->allowSubmit();
    }

    public static function scopeAllowUserSubmitQuestions($query, $userId)
    {
        $categoryTable     = (new Category)->getTable();
        $categoryUserTable = (new CategoryUser)->getTable();

        //官方允许出题的
        $query->select("${categoryTable}.*")
            ->whereNotIn("${categoryTable}.id", [Category::RECOMMEND_VIDEO_QUESTION_CATEGORY])
            ->leftJoin("${categoryUserTable}", function ($join) use ($userId, $categoryTable, $categoryUserTable) {
                $join->on("${categoryTable}.id", "${categoryUserTable}.category_id")
                    ->on("${categoryUserTable}.user_id", DB::raw($userId));
            })->publishedQuestionTypeAndAllowSubmit()
            ->latest("${categoryUserTable}.correct_count");

        return $query;
    }

    public static function scopeAllowUserAuditQuestions($query, $userId)
    {
        $categoryTable     = (new Category)->getTable();
        $categoryUserTable = (new CategoryUser)->getTable();

        //官方允许出题的
        $query->select("${categoryTable}.*")
            ->whereNotIn("${categoryTable}.id", [Category::RECOMMEND_VIDEO_QUESTION_CATEGORY])
            ->leftJoin("${categoryUserTable}", function ($join) use ($userId, $categoryTable, $categoryUserTable) {
                $join->on("${categoryTable}.id", "${categoryUserTable}.category_id")
                    ->on("${categoryUserTable}.user_id", DB::raw($userId))
                    ->where("${categoryUserTable}.can_audit", true);
            })->published()->questionType();

        return $query;
    }

    public function scopeSearch($query, $keyword, $field = 'name')
    {
        $field = $this->getTable() . '.' . $field;
        return !empty($keyword) ? $query->where($field, 'like', "$keyword%")->orWhere(DB::raw("reverse($field)"), 'like', "$keyword%") : $query;
    }

    public function scopeOfQuestion($query)
    {
        return $query->where('type', Category::QUESTION_TYPE_ENUM);
    }

    public function scopeRootNode($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeHasQuestion($query)
    {
        return $query->where('questions_count', '>', 0);
    }

    public function questionsWithChildren()
    {
        $ids = $this->children()->select('id')->pluck('id')->toArray();
        array_push($ids, $this->id);
        return Question::whereIn('category_id', [$ids]);
    }

    public function scopeHasChildren($query, $count = 1)
    {
        return $query->where('children_count', '>=', $count);
    }

}
