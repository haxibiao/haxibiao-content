<?php

namespace Haxibiao\Content\Nova;

use App\Nova\Resource;
use Haxibiao\Breeze\Nova\User;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;

class Stick extends Resource
{

    public static $group          = "小编精选";
    public static $perPageOptions = [25, 50, 100, 500, 1000];
    public static function label()
    {
        return '置顶';
    }

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Stick::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
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
            Text::make('展示位置', 'place')->suggestions(\App\Stick::getAppPlaces()),
            Text::make('app名称', 'app_name')->withMeta(['value' => config('app.name')]),
            BelongsTo::make('网站名称', 'site', Site::class)->nullable(),
            Image::make('封面', 'cover')
                ->thumbnail(function () {
                    return $this->cover;
                })->store(function (Request $request, $model) {
                $file = $request->file('cover');
                return $model->saveDownloadImage($file);
            })->preview(function () {
                return $this->cover;
            })->disableDownload(),

            BelongsTo::make('精选对象', 'editorChoice', EditorChoice::class),
            Number::make('权重', 'rank')->withMeta(['value' => 0]),
            BelongsTo::make('置顶人员', 'editor', User::class),
            DateTime::make('创建时间', 'created_at')->hideWhenCreating(),
            DateTime::make('更新时间', 'updated_at')->hideWhenCreating(),
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
