<?php

namespace Haxibiao\Content\Nova\Filters;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Filters\Filter;

class MoviesByRegion extends Filter
{
    public $name = '电影地区';

    public $component = 'select-filter';
    /**
     * Apply the filter to the given query.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(Request $request, $query, $value)
    {
        return $query->where('region', $value);
    }

    /**
     * Get the filter's available options.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function options(Request $request)
    {
        $options      = [];
        $movieRegions = DB::table('movies')->distinct('region')->get();
        foreach ($movieRegions as $item) {
            $options[$item->region] = $item->region;
        }

        return $options;
    }
}
