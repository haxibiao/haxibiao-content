<?php

namespace Haxibiao\Content\Nova;

use App\Nova\Resource;
use Haxibiao\Content\Nova\Metrics\BaiduIncludeTrend;
use Haxibiao\Content\Nova\Metrics\SiteSpiderPartition;
use Haxibiao\Content\Nova\Metrics\SiteTrafficPartition;
use Haxibiao\Content\Nova\Metrics\SpiderPartition;
use Haxibiao\Content\Nova\Metrics\SpiderTrend;
use Haxibiao\Content\Nova\Metrics\TrafficPartition;
use Haxibiao\Content\Nova\Metrics\TrafficTrend;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;

class Traffic extends Resource
{
    public static $group = 'SEO中心';
    public static $model = 'App\Traffic';
    public static function label()
    {
        return "SEO流量";
    }
    public static $title  = 'name';
    public static $search = [
        'id', 'url', 'bot', 'engine',
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
            ID::make()->sortable(),
            Text::make('URL', function () {
                return '<a class="btn btn-link" target="_blank" href="' . $this->url . '">' . str_limit($this->url, 40) . '</a>';
            })->asHtml(),
            Text::make('站点', 'domain')->hideFromIndex(),
            Text::make('蜘蛛', 'bot'),
            Text::make('引擎', 'engine'),
            Text::make('来源URL', 'referer')->hideFromIndex(),
            DateTime::make('时间', 'created_at'),
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
            (new SpiderPartition)->width('1/4'),
            (new SpiderTrend)->width('1/4'),
            (new TrafficPartition)->width('1/4'),
            (new TrafficTrend)->width('1/4'),
            (new SiteTrafficPartition)->width('1/4'),
            (new SiteSpiderPartition)->width('1/4'),
            (new BaiduIncludeTrend)->width('1/4'),
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
