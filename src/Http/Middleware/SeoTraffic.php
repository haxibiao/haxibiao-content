<?php

namespace Haxibiao\Content\Http\Middleware;

use Closure;
use Haxibiao\Content\Traffic;
use Jenssegers\Agent\Facades\Agent;

class SeoTraffic
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $traffic = [];
        //蜘蛛抓取
        if (Agent::isRobot()) {
            $bot = strtolower(Agent::robot());
            if (str_contains($bot, 'baidu') ||
                str_contains($bot, 'google') ||
                str_contains($bot, 'qihoo') ||
                str_contains($bot, '360') ||
                str_contains($bot, 'sogou') ||
                str_contains($bot, 'shenma') ||
                str_contains($bot, 'toutiao') ||
                str_contains($bot, 'byte')
            ) {
                $traffic['bot'] = $bot;
            }
        }

        //搜索来路
        $referer = $request->get('referer') ?? $request->header('referer');
        if ($referer) {
            if (str_contains($referer, 'baidu.com')) {
                $engine = 'baidu';
            }
            if (str_contains($referer, 'google.com')) {
                $engine = 'google';
            }
            if (str_contains($referer, '360.cn')) {
                $engine = '360';
            }
            if (str_contains($referer, 'sogou')) {
                $engine = 'sogou';
            }
            if (str_contains($referer, 'shenma')) {
                $engine = 'shenma';
            }
            if (str_contains($referer, 'toutiao')) {
                $engine = 'toutiao';
            }
            if (str_contains($referer, 'byte')) {
                $engine = 'byte';
            }
            if (isset($engine)) {
                $traffic['engine']  = $engine;
                $traffic['referer'] = $referer;
            }
        }

        //如果seo有效流量
        if (!empty($traffic)) {
            $traffic['url']    = $request->url();
            $traffic['domain'] = get_domain();

            //记录流量
            Traffic::create($traffic);
        }

        return $next($request);
    }
}
