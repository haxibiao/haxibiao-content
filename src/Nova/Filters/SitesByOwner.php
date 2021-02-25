<?php

namespace Haxibiao\Content\Nova\Filters;

use App\Site;
use Illuminate\Http\Request;
use Laravel\Nova\Filters\Filter;

class SitesByOwner extends Filter
{
    public $name = '站长';

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
        return $query->where('owner', $value);
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
        foreach (Site::all() as $site) {
            $options[$site->owner] = $site->owner;
        }

        return $options;
    }
}
