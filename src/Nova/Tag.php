<?php

namespace Haxibiao\Content\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\MorphToMany;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Resource;

class Tag extends Resource
{
    public static $model = "Haxibiao\Content\Tag";

    public static $title = 'id';

    public static $group = '内容中心';

    public static function label()
    {
        return "标签";
    }

    public static function singularLabel()
    {
        return "标签";
    }

    public static $search = [
        'name',
    ];

    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),
            Text::make('名字', 'name')
                ->exceptOnForms(),
            Number::make('标记次数', 'count')
                ->exceptOnForms()->sortable(),
            MorphToMany::make('posts')
                ->exceptOnForms(),
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
