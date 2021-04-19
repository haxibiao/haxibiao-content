<?php

namespace Haxibiao\Content\Nova;

use Halimtuhu\ArrayImages\ArrayImages;
use Haxibiao\Content\Nova\Actions\AssignToSite;
use Haxibiao\Content\Nova\Actions\StickyToSite;
use Haxibiao\Content\Nova\Filters\ArticleByType;
use Haxibiao\Content\Nova\Filters\ArticleStatusType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;

class SiteArticle extends Resource
{
    public static $group          = 'SEO中心';
    public static $perPageOptions = [25, 50, 100, 500, 1000];
    public static $model          = 'App\Article';
    public static $title          = 'title';
    public static $with           = ['user', 'category', 'video'];
    public static $search         = [
        'id', 'title',
    ];
    public static function label()
    {
        return "文章";
    }

    //过滤草稿状态的
    public static function indexQuery(NovaRequest $request, $query)
    {
        return $query->whereStatus(1);
    }

    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),
            Text::make('相关文字', function () {
                $text = $this->title;
                if (empty($text)) {
                    $text = $this->body;
                }
                $text = str_limit($text);
                return '<a style="width: 300px" href="articles/' . $this->id . '">' . $text . "</a>";
            })->asHtml()->onlyOnIndex(),
            Text::make('文章标题', 'title')->hideFromIndex()->hideWhenCreating(),
            Text::make('评论数', function () {
                return $this->comments()->count();
            })->hideWhenCreating(),
            Text::make('点赞数', 'count_likes')->hideWhenCreating(),

            Textarea::make('文章内容', 'body')->rules('required')->hideFromIndex(),
            Select::make('状态', 'status')->options([
                1  => '公开',
                0  => '草稿',
                -1 => '下架',
            ])->displayUsingLabels(),

            Select::make('类型', 'type')->options([
                'post'  => '动态',
                'issue' => '问答',
            ])->displayUsingLabels(),

            Select::make('审核', 'submit')->options(\App\Article::getSubmitStatus())->displayUsingLabels(),

            BelongsTo::make('作者', 'user', 'App\Nova\User')->exceptOnForms(),
            BelongsTo::make('分类', 'category', 'App\Nova\Category')->withMeta([
                'belongsToId' => 1, //$this->NovaDefaultCategory(), //指定默认分类
            ]),
            Text::make('时间', function () {
                return time_ago($this->created_at);
            })->onlyOnIndex(),
            // Number::make('总评论数', 'count_comments')->exceptOnForms()->sortable(),
            // File::make('上传视频', 'video_id')->onlyOnForms()->store(
            //     function (Request $request, $model) {
            //         $file      = $request->file('video_id');
            //         $validator = Validator::make($request->all(), [
            //             'video' => 'mimetypes:video/avi,video/mp4,video/mpeg,video/quicktime',
            //         ]);
            //         if ($validator->fails()) {
            //             return '视频格式有问题';
            //         }
            //         return $model->saveVideoFile($file);
            //     }
            // ),

            // ArrayImages::make('图片', function () {
            //     return $this->screenshots;
            // }),
            Text::make('百度提交', 'baidu_pushed_at'),
            Text::make('备注', 'remark')->onlyOnDetail(),
        ];
    }

    public function cards(Request $request)
    {
        return [];
    }

    public function filters(Request $request)
    {
        return [
            new ArticleByType,
            new ArticleStatusType,
        ];
    }

    public function lenses(Request $request)
    {
        return [];
    }

    public function actions(Request $request)
    {
        return [
            new AssignToSite,
            (new StickyToSite)->withMeta(['type' => 'articles']),
        ];
    }
}
