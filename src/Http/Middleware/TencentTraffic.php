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
            //配置了跳转地址
            $redirect_urls = config('cms.tencent_traffic.redirect_urls');
            //尊重当前域名缓存的跳转地址
            if ($cached_urls = cache()->get(get_sub_domain() . '_redirect_urls')) {
                $redirect_urls = $cached_urls;
            }
            if (!empty($redirect_urls)) {
                //支持站群多入口域名防护被腾讯污染!!!
                $income_domains   = config('cms.tencent_traffic.income_domains');
                $income_domains[] = config('cms.tencent_traffic.income_domain');
                if (in_array(get_sub_domain(), $income_domains)) {
                    if ($url = array_random($redirect_urls)) {
                        return redirect()->to($url);
                    }
                }
            }
        }
        return $next($request);
    }
}
