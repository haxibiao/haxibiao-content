<?php

namespace Haxibiao\Content\Nova\Filters;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Filters\Filter;

class MoviesByYear extends Filter
{
    public $name = '电影年份';

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
        return $query->where('year', $value);
    }

    /**
     * Get the filter's available options.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function options(Request $request)
    {
        $options = [];
        $items   = DB::table('movies')->distinct('year')->get();
        foreach ($items as $item) {
            $options[$item->year] = $item->year;
        }

        return $options;
    }
}
