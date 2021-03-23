<?php

namespace Haxibiao\Content\Nova;

use App\Nova\Resource;
use Haxibiao\Content\Nova\Actions\SeoWorkAction;
use Haxibiao\Content\Nova\Metrics\SiteOwnerPartition;
use Haxibiao\Content\Nova\SiteArticle;
use Haxibiao\Content\Nova\SitePost;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\MorphedByMany;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class Site extends Resource
{
    public static $group = 'SEO中心';
    public static $model = 'App\Site';
    public static function label()
    {
        return "站群";
    }
    public static $title  = 'name';
    public static $search = [
        'id', 'name', 'domain',
    ];

    //过滤非激活状态的站点
    public static function indexQuery(NovaRequest $request, $query)
    {
        return $query->whereActive(1);
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
            Text::make('名称', 'name'),
            Text::make('站长', 'owner'),
            Text::make('域名', 'domain'),
            Text::make('域名', function () {
                return '<a href="//' . $this->domain . '" target="_blank">' . $this->domain . '</a>';
            })->asHtml(),
            Text::make('模板主题', 'theme'),
            Boolean::make('活跃', 'active'),
            Select::make('备案信息模版', 'company')->options(
                array_combine(
                    array_keys(config('cms.icp')),
                    array_keys(config('cms.icp'))
                )
            ),
            Text::make('百度Token', 'ziyuan_token')->hideFromIndex(),
            Text::make('神马Token', 'shenma_token')->hideFromIndex(),
            Text::make('神马站长邮箱', 'shenma_owner_email')->hideFromIndex()->placeholder('自动提交MIP数据到神马需要'),
            Text::make('360Token', '360_token')->hideFromIndex()->placeholder('暂未支持'),
            Text::make('搜狗Token', 'sogou_token')->hideFromIndex()->placeholder('暂未支持'),
            Text::make('头条Token', 'toutiao_token')->hideFromIndex()->placeholder('暂未支持'),
            Text::make('SEO标题', 'title')->hideFromIndex(),
            Text::make('SEO关键词', 'keywords')->hideFromIndex(),
            Text::make('SEO描述', 'description')->hideFromIndex(),
            Textarea::make('站长验证Meta', 'verify_meta')->hideFromIndex()->placeholder("主要验证站长身份"),
            Textarea::make('网站底部JS', 'footer_js')->hideFromIndex()->placeholder('自动提交push,第三方统计js...'),
            Text::make('文章数', function () {
                return $this->articles()->count();
            }),
            Text::make('视频数', function () {
                return $this->posts()->count();
            }),
            Text::make('电影数', function () {
                return $this->movies()->count();
            }),
            Text::make('查询配额', function () {
                return '成功:' . $this->baidu_success . ', 剩余:' . $this->baidu_remain . ' <a href="//' . $this->domain . '/seo/pushResult" target="_blank">刷新百度</a>';
            })->asHtml(),

            Text::make('今日百度提交', function () {
                $count_movies_pushed   = $this->movies()->where('siteables.baidu_pushed_at', '>', today())->count();
                $count_posts_pushed    = $this->posts()->where('siteables.baidu_pushed_at', '>', today())->count();
                $count_articles_pushed = $this->articles()->where('siteables.baidu_pushed_at', '>', today())->count();
                return $count_movies_pushed + $count_posts_pushed + $count_articles_pushed;
            }),

            MorphedByMany::make('文章', 'articles', SiteArticle::class),
            MorphedByMany::make('视频动态', 'posts', SitePost::class),
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
        return [
            new SiteOwnerPartition,
        ];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [
            // new SitesByOwner,
        ];
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
            new SeoWorkAction,
        ];
    }
}
