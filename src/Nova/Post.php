<?php

namespace Haxibiao\Content\Nova;

use App\Nova\Actions\Article\UpdatePost;
use App\Nova\Resource;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;

class Post extends Resource
{
    //public static $title = 'description';

    public static $model = 'App\\Post';

    public static $group = '内容管理';

    public static $search = [
        'id', 'content',
    ];

    public static $with = ['user', 'video'];
    public static function label()
    {
        return "动态";
    }

    public static function singularLabel()
    {
        return "动态";
    }

    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),
            Textarea::make('文章内容', 'content')->rules('required')->hideFromIndex(),
            BelongsTo::make('作者', 'user', 'App\Nova\User')->exceptOnForms(),
            BelongsTo::make('视频', 'video', Video::class)->exceptOnForms(),
            Text::make('描述', 'description')->exceptOnForms(),
            Text::make('热度', 'hot')->exceptOnForms(),
            Text::make('点赞', 'count_likes')->exceptOnForms(),
            Text::make('评论', 'count_comments')->exceptOnForms(),
            Select::make('状态', 'status')->options([
                1  => '公开',
                0  => '草稿',
                -1 => '下架',
            ])->displayUsingLabels(),

            Text::make('视频', function () {
                if ($this->video) {
                    return "<div style='width:150px; overflow:hidden;'><video controls style='widht:150px; height:300px' src='" . $this->video->url . "?t=" . time() . "'></video></div>";
                }
                return '';
            })->asHtml(),

            // File::make('上传视频', 'video_id')->onlyOnForms()->store(
            //     function (Request $request, $model) {
            //         $file      = $request->file('video_id');
            //         $validator = Validator::make($request->all(), [
            //             'video' => 'mimetypes:video/avi,video/mp4,video/mpeg,video/quicktime',
            //         ]);
            //         if ($validator->fails()) {
            //             return '视频格式有问题';
            //         }
            //         return \App\Video::uploadNovaVod($file);
            //     }
            // ),
        ];
    }

    public function cards(Request $request)
    {
        return [];
    }

    public function filters(Request $request)
    {
        return [
            // new Filters\Post\PostStatusType,
        ];
    }

    public function lenses(Request $request)
    {
        return [];
    }

    public function actions(Request $request)
    {
        return [
            new UpdatePost,
        ];
    }
}
