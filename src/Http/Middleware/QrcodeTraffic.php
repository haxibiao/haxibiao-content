<?php

namespace Haxibiao\Content\Http\Middleware;

use Closure;

class QrcodeTraffic
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
        //腾讯的流量跳转的前提
        $can_redirect = $request->secure() && (isWechat() || isQQ());
        //referer是四级以上域名是已跳转过的
        $referer      = $request->header('referer');
        $referer_host = parse_url($referer, PHP_URL_HOST);
        if (count(explode(".", $referer_host)) >= 4) {
            $can_redirect = false;
        }
        if ($can_redirect) {
            $redirect_urls = config('cms.qrcode_traffic.redirect_urls');
            //尊重当前域名缓存的跳转地址
            if ($cached_urls = cache()->get(get_sub_domain() . '_redirect_urls')) {
                $redirect_urls = $cached_urls;
            }
            //支持站群多入口域名防护被腾讯污染!!!
            $scan_domains = array_keys(config('cms.qrcode_traffic.scan_domains', []));
            //支持不同入口域名覆盖自己的跳转地址
            $sub_urls = $scan_domains[get_sub_domain()] ?? [];
            if (!empty($sub_urls)) {
                $redirect_urls = $sub_urls;
            }
            if (!empty($redirect_urls)) {
                if (in_array(get_sub_domain(), $scan_domains)) {
                    if ($url = array_random($redirect_urls)) {
                        return redirect()->to($url);
                    }
                }
            }
        }
        return $next($request);
    }
}
