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
        // 腾讯的流量跳转的前提
        // 1.SSL
        // 2.腾讯系浏览器 - 可增加
        // 3.referer不是四级以上域名
        $can_redirect = $request->secure() && (isWechat() || isQQ());
        $referer      = $request->header('referer');
        $referer_host = parse_url($referer, PHP_URL_HOST);
        if (count(explode(".", $referer_host)) >= 4) {
            $can_redirect = false;
        }
        if ($can_redirect) {

            // 跳转的域名匹配
            $scan_domains = array_keys(config('cms.qrcode_traffic.scan_domains', []));
            if ($scan_domain = config('cms.qrcode_traffic.scan_domain')) {
                $scan_domains = [$scan_domain]; //强制单独二维码入口域名权重最高
            }
            $domain_match = in_array(get_sub_domain(), $scan_domains);

            // 跳转地址的提取
            $redirect_urls = config('cms.qrcode_traffic.redirect_urls', []);
            // 1.支持当前域名cms配置覆盖的跳转地址
            $sub_urls = $scan_domains[get_sub_domain()] ?? [];
            if (!empty($sub_urls)) {
                $redirect_urls = $sub_urls;
            }
            // 2.尊重当前域名缓存的跳转地址
            if ($cached_urls = cache()->get(get_sub_domain() . '_redirect_urls')) {
                $redirect_urls = $cached_urls;
            }

            // 3.最后随机提取一个可用的
            $redirect_url = array_random($redirect_urls);

            // 最后跳转
            if ($domain_match && $redirect_url) {
                return redirect()->to($redirect_url);
            }
        }
        return $next($request);
    }
}
