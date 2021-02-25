<?php

namespace Haxibiao\Content\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Resource;

class Stickable extends Resource
{
    public static $model = 'App\Stickable';
    public static $title = 'id';

    public static $group = 'SEO中心';
    public static function label()
    {
        return "置顶";
    }

    public static $search = [
        'id', 'name',
    ];

    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),
            Text::make('名称', 'name'),
            Select::make('置顶类型', 'stickable_type')->options([
                'Video'   => '短视频',
                'Article' => '图文',
                'Movie'   => '电影',
            ]),
            Text::make('置顶id', 'stickable_id'),
            Text::make('页面', 'page'),
            Select::make('位置', 'area')->options([
                '上' => '上',
                '下' => '下',
                '左' => '左',
                '右' => '右',
            ]),
        ];
    }

    public function cards(Request $request)
    {
        return [];
    }

    public function filters(Request $request)
    {
        return [];
    }

    public function lenses(Request $request)
    {
        return [];
    }

    public function actions(Request $request)
    {
        return [];
    }
}
