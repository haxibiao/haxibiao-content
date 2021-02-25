<?php

namespace Haxibiao\Content\Nova\Metrics;

use App\Site;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Partition;

class SiteOwnerPartition extends Partition
{
    public $name = '站长分布';
    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function calculate(Request $request)
    {
        $qb = Site::whereNotNull('owner');
        return $this->count($request, $qb, 'owner');
    }

    /**
     * Determine for how many minutes the metric should be cached.
     *
     * @return  \DateTimeInterface|\DateInterval|float|int
     */
    public function cacheFor()
    {
        // return now()->addMinutes(5);
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'site-owner-partition';
    }
}
