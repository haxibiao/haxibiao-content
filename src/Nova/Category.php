<?php

namespace Haxibiao\Content\Nova;

use App\Nova\Resource;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;

class Category extends Resource
{
    // public static $displayInNavigation = false;
    public static $model  = 'App\\Category';
    public static $title  = 'name';
    public static $search = [
        'id', 'name', 'name_en',
    ];
    public static function label()
    {
        return "专题";
    }
    public static $group = '内容中心';

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),
            Text::make('类型', 'type'),
            Text::make('分类名称', 'name'),
            Text::make('分类英文名', 'name_en'),
            Select::make('官方专题', 'is_official')->options([1 => '是', 0 => '否'])->onlyOnForms(),
            Select::make('状态', 'status')->options([
                1 => '上架',
                0 => '隐藏',
            ])->displayUsingLabels(),
            BelongsTo::make('作者', 'user', \App\Nova\User::class),
            HasMany::make('文章', 'hasManyArticles', Article::class),
            Text::make('视频数量', function () {
                return $this->containedVideoPosts()->count();
            }),
            Text::make('时间', function () {
                return time_ago($this->created_at);
            }),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [
            // (new \Hxb\CategoryCount\CategoryCount)
            //     ->withName("分类下的视频数量前十个统计")
            //     ->withLegend("视频数量")
            //     ->withColor("#FF00FF")
            //     ->withData(AppCategory::getTopCategory(10)),
        ];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [
            new \Haxibiao\Breeze\Nova\Filters\Task\TaskStatusType,
        ];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }
}
