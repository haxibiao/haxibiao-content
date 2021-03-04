<?php

namespace Haxibiao\Content\Nova;

use App\Nova\Resource;
use Haxibiao\Breeze\Nova\User;
use Haxibiao\Content\Nova\Site;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Text;

class Stick extends Resource
{
    public static $group          = "置顶系统";
    public static $perPageOptions = [25, 50, 100, 500, 1000];
    public static function label()
    {
        return '置顶';
    }
    public static $title = 'id';
    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make(__('ID'), 'id')->sortable(),
            Text::make('展示位置', 'place'),
            Text::make('app名字', 'app_name'),
            Image::make('图片', 'path')
                ->thumbnail(function () {
                    return $this->cover;
                })->preview(function () {
                return $this->cover;
            })->disableDownload(),
            BelongsTo::make('精选', 'editorChoice', EditorChoice::class),
            BelongsTo::make('网站', 'site', Site::class),
            BelongsTo::make('小编', 'editor', User::class),
            DateTime::make('创建时间', 'created_at'),
            DateTime::make('更新时间', 'updated_at'),
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
        return [];
    }
}
