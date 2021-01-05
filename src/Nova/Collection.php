<?php

namespace Haxibiao\Content\Nova;

use App\Nova\Resource;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;

class Collection extends Resource
{

    public static $model = 'App\\Collection';

    public static $group = '内容管理';

    public static $search = [
        'id', 'content',
    ];

    public static $with = ['user'];
    public static function label()
    {
        return "合集";
    }

    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),
            Textarea::make('名称', 'name')->rules('required')->hideFromIndex(),
            BelongsTo::make('作者', 'user', 'App\Nova\User')->exceptOnForms(),
            Text::make('说明', 'description')->exceptOnForms(),
            Text::make('集数', 'count')->exceptOnForms(),
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
