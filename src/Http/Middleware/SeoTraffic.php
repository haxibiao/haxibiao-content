<?php

namespace Haxibiao\Content\Http\Middleware;

use Closure;
use Haxibiao\Breeze\Dimension;
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
        if(!Agent::isRobot()){
            return $next($request);
        }

        // 爬虫来源引擎
        $engine = $this->getEngineFromRequest();
        if($engine){
            Dimension::track($engine, 1, '爬虫的次数');
        }

        // 搜索来路引擎
        $referer = $this->getRefererEngineByRequest($request);
        if($referer){
            Dimension::track($referer, 1, '搜索来路');
        }

        return $next($request);
    }

    private function getEngineFromRequest(){
        $bot = strtolower(Agent::robot());
        return $this->getEngineByLowerStr($bot);
    }

    private function getRefererEngineByRequest($request){
        $referer = $request->get('referer') ?? $request->header('referer');
        $referer = str_limit($referer, 250, '');
        $referer = strtolower($referer);
        return $this->getEngineByLowerStr($referer);
    }

    private function getEngineByLowerStr($str=null){
        if(blank($str)){
            return null;
        }
        if (str_contains($str, 'baidu.com')) {
            $engine = 'baidu';
        }
        if (str_contains($str, 'google.com')) {
            $engine = 'google';
        }
        if (str_contains($str, '360.cn')) {
            $engine = '360';
        }
        if (str_contains($str, 'sogou')) {
            $engine = 'sogou';
        }
        if (str_contains($str, 'shenma')) {
            $engine = 'shenma';
        }
        if (str_contains($str, 'toutiao')) {
            $engine = 'toutiao';
        }
        if (str_contains($str, 'byte')) {
            $engine = 'byte';
        }
        if (str_contains($str, 'bing')) {
            $engine = 'bing';
        }
        if (isset($engine)) {
            return $engine;
        }
    }
}
