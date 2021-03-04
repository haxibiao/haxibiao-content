<?php

namespace Haxibiao\Content\Nova;

use Haxibiao\Content\Nova\Actions\AddMovieToSticks;
use Haxibiao\Content\Nova\Actions\AssignToSite;
use Haxibiao\Content\Nova\Actions\StickyToSite;
use Haxibiao\Content\Nova\Filters\MoviesByRegion;
use Haxibiao\Content\Nova\Filters\MoviesByStyle;
use Haxibiao\Content\Nova\Filters\MoviesByType;
use Haxibiao\Content\Nova\Filters\MoviesByYear;
use Haxibiao\Media\Nova\Movie as NovaMovie;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\NovaRequest;

class SiteMovie extends NovaMovie
{
    public static $group          = "SEO中心";
    public static $perPageOptions = [25, 50, 100, 500, 1000];
    public static $model          = 'App\Movie';

    //过滤草稿状态的
    public static function indexQuery(NovaRequest $request, $query)
    {
        // return $query->whereStatus(1);
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        //暂时海外服务器(hk都OK)这里site-movie filters奇怪的500错误未定位
        if (is_prod_env()) {
            $safe_servers = str_contains(gethostname(), 'gz') || str_contains(gethostname(), 'hk');
            if (!$safe_servers) {
                return [];
            }
        }

        return [
            new MoviesByRegion,
            new MoviesByYear,
            new MoviesByType,
            new MoviesByStyle,
        ];
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
            (new StickyToSite)->withMeta(['type' => 'movies']),
            new AddMovieToSticks,
        ];
    }
}
