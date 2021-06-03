<?php

namespace Haxibiao\Content\Nova\Filters;

use Haxibiao\Media\Movie;
use Illuminate\Http\Request;
use Laravel\Nova\Filters\Filter;

class MoviesByStatus extends Filter
{
    public $name = '电影状态';

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
        return $query->where('status', $value);
    }

    /**
     * Get the filter's available options.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function options(Request $request)
    {
        return array_flip(Movie::getStatuses());
    }
}
