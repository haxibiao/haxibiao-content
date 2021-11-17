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
            //配置了防拦截候选地址
            $redirect_urls = config('cms.tencent_traffic.redirect_urls');
            if (!empty($redirect_urls)) {
                //随机跳转一个防拦截的h5(pwa)地址,不用自己的主域名，入口域名
                if (in_array(get_sub_domain(), [
                    config('cms.tencent_traffic.income_domain'),
                    env('APP_DOMAIN'),
                ])) {
                    $url = array_random($redirect_urls);
                }

                return redirect()->to($url);
            }
        }
        return $next($request);
    }
}
