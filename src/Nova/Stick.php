<?php

namespace Haxibiao\Content\Nova;

use App\Nova\Collection;
use App\Nova\Post;
use App\Nova\Resource;
use Haxibiao\Breeze\Nova\User;
use Haxibiao\Media\Nova\Movie;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Fields\Select;
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
            Text::make('展示位置', 'place')->suggestions([
                '影厅顶部',
                '合集顶部',
            ]),
            Text::make('app名字', 'app_name'),
            Image::make('封面', 'cover')
                ->thumbnail(function () {
                    return $this->cover;
                })->store(function (Request $request, $model) {
                $file = $request->file('cover');
                return $model->saveDownloadImage($file);
            })->preview(function () {
                return $this->cover;
            })->disableDownload(),
            // MorphTo::make('定制对象', 'stickable')->types([
            //     Movie::class,
            //     Post::class,
            //     Collection::class,
            // ]),

            Select::make('定制对象','stickable_type')->options([
                'movies' => '电影',
                'posts' => '动态',
                'collections' => '合集',
            ]),
            Text::make('定制对象ID', 'stickable_id'),
            BelongsTo::make('精选', 'editorChoice', EditorChoice::class),
            BelongsTo::make('网站', 'site', Site::class)->nullable(),
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
