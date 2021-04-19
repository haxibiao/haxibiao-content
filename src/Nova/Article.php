<?php

namespace Haxibiao\Content\Nova;

use App\Nova\Resource;
use Haxibiao\Content\Nova\Actions\UpdateArticle;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;

class Article extends Resource
{

    public static $model = 'App\\Article';

    public static $group = '内容中心';

    public static $search = [
        'id', 'content',
    ];

    public static $with = ['user', 'video'];
    public static function label()
    {
        return "文章";
    }

    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),
            Textarea::make('标题', 'title')->rules('required')->hideFromIndex(),
            BelongsTo::make('作者', 'user', 'App\Nova\User')->exceptOnForms(),
            // BelongsTo::make('视频', 'video', Video::class)->exceptOnForms(),
            Text::make('描述', 'description')->exceptOnForms(),
            Text::make('热度', 'hits')->exceptOnForms(),
            Text::make('点赞', 'count_likes')->exceptOnForms(),
            Text::make('评论', 'count_comments')->exceptOnForms(),
            Select::make('状态', 'status')->options([
                1  => '公开',
                0  => '草稿',
                -1 => '下架',
            ])->displayUsingLabels(),

            // Text::make('视频', function () {
            //     if ($this->video) {
            //         return "<div style='width:150px; overflow:hidden;'><video controls style='widht:150px; height:300px' src='" . $this->video->url . "?t=" . time() . "'></video></div>";
            //     }
            //     return '';
            // })->asHtml(),
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
        return [
            new UpdateArticle,
        ];
    }
}
