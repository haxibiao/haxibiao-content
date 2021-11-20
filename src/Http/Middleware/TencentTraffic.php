<?php

namespace Haxibiao\Content\Http\Middleware;

use Closure;

class TencentTraffic
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
        //如果是腾讯的流量
        if (isWechat() || isQQ()) {
            //如果配置了防拦截候选地址
            $redirect_urls = config('cms.tencent_traffic.redirect_urls');
            if (!empty($redirect_urls)) {
                //应该保护任何入口域名被腾讯污染!!!
                if ($url = array_random($redirect_urls)) {
                    return redirect()->to($url);
                }
            }
        }
        return $next($request);
    }
}
