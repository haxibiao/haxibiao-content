<?php

namespace Haxibiao\Content\Nova\Metrics;

use App\Site;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Trend;
use Laravel\Nova\Metrics\TrendResult;

class BaiduIncludeTrend extends Trend
{
    public $name = '百度索引量变化趋势';
    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function calculate(Request $request)
    {
        $range = $request->range;
        $data  = [];

        $site = \App\Site::where('domain', $range)->first();

        if ($site === null) {
            return (new TrendResult(end($data)))->trend($data)
                ->suffix("昨日: 0 最大: 0");
        }
        $json = $site->json;
        if ($json) {
            foreach ($json['baidu'] as $date => $value) {
                $data[$date] = $value;
            }
        } else {
            for ($i = 29; $i >= 0; $i--) {
                $data[today()->subday($i)->toDateString()] = 0;
            }
        }

        $max       = max($data);
        $yesterday = $data[today()->subday(1)->toDateString()];

        return (new TrendResult(end($data)))->trend($data)
            ->suffix("昨日: $yesterday 最大: $max");
    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array
     */
    public function ranges()
    {
        $result = [];
        foreach (Site::whereActive(true)->get() as $site) {
            $result[$site->domain] = $site->name;
        }
        return $result;
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
        return 'baidu-include-trend';
    }
}
