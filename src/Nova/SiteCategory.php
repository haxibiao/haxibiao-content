<?php

namespace Haxibiao\Content\Nova;

use App\Nova\Article;
use App\Nova\Resource;
use App\Nova\User;
use Haxibiao\Content\Nova\Actions\AssignToSite;
use Haxibiao\Content\Nova\Actions\StickyToSite;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;

class SiteCategory extends Resource
{
    // public static $displayInNavigation = false;
    public static $group          = 'SEO中心';
    public static $model          = 'App\Category';
    public static $title          = 'name';
    public static $perPageOptions = [25, 50, 100, 500, 1000];
    public static $search         = [
        'id', 'name', 'name_en',
    ];
    public static function label()
    {
        return "专题";
    }

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
            BelongsTo::make('作者', 'user', User::class),
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
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
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
        return [
            new AssignToSite,
            (new StickyToSite)->withMeta(['type' => 'categories']),
        ];
    }
}
