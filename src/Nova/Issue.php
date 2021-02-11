<?php

namespace Haxibiao\Content\Nova;

use App\Nova\Resource;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;

class Issue extends Resource
{

    public static $model = 'App\\Issue';

    public static $group = '内容中心';

    public static $search = [
        'id', 'content',
    ];

    public static $with = ['user'];
    public static function label()
    {
        return "问答";
    }

    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),
            Textarea::make('标题', 'title')->rules('required')->hideFromIndex(),
            BelongsTo::make('作者', 'user', 'App\Nova\User')->exceptOnForms(),
            Text::make('背景说明', 'background')->exceptOnForms(),
            Text::make('热度', 'hits')->exceptOnForms(),
            Text::make('点赞', 'count_likes')->exceptOnForms(),
            Select::make('状态', 'status')->options([
                1  => '公开',
                0  => '草稿',
                -1 => '下架',
            ])->displayUsingLabels(),
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
