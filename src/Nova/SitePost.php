<?php

namespace Haxibiao\Content\Nova;

use App\Nova\User;
use Haxibiao\Content\Nova\Actions\AssignToSite;
use Haxibiao\Content\Nova\Actions\StickyToSite;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;

class SitePost extends Resource
{
    public static $group          = "SEO中心";
    public static $perPageOptions = [25, 50, 100, 500, 1000];
    public static function label()
    {
        return '短视频';
    }
    public static $model  = 'App\Post';
    public static $title  = 'title';
    public static $search = [
        'id', 'content',
    ];

    //过滤草稿状态的
    public static function indexQuery(NovaRequest $request, $query)
    {
        return $query->whereStatus(1);
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
            Text::make('内容', 'content')->hideWhenCreating(),
            BelongsTo::make('上传用户', 'user', User::class)->onlyOnForms(),
            Text::make('点赞', 'count_likes')->hideWhenCreating(),
            Textarea::make('描述', 'description'),
            Select::make('状态', 'status')->options([
                1  => '公开',
                0  => '草稿',
                -1 => '下架',
            ])->displayUsingLabels(),
            BelongsTo::make('作者', 'user', 'App\Nova\User')->exceptOnForms(),
            Text::make('时间', function () {
                return time_ago($this->created_at);
            })->onlyOnIndex(),
            // Number::make('总评论数', 'count_comments')->exceptOnForms()->sortable(),
            Image::make('图片', 'video.cover')->thumbnail(
                function () {
                    return $this->cover;
                }
            )->preview(
                function () {
                    return $this->cover;
                }
            ),
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
            (new StickyToSite)->withMeta(['type' => 'posts']),
        ];
    }
}
