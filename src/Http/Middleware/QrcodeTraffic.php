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
        // 二维码流量跳转的前提
        // - 腾讯系浏览器 - 可增加
        $can_redirect = isWechat() || isQQ();
        // - referer为空(扫码识别直接打开的地址)
        $referer = $request->header('referer');
        if (!blank($referer)) {
            $can_redirect = false;
        }
        // - 首页或者影片详情页
        if (!($request->path() === '/' || str_contains($request->path(), 'movie/'))) {
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
            $sub_urls = config('cms.qrcode_traffic.scan_domains')[get_sub_domain()]['redirect_urls'] ?? [];
            if (!empty($sub_urls)) {
                $redirect_urls = $sub_urls;
            }
            // 2.尊重当前域名缓存的跳转地址
            if ($cached_urls = cache()->get(get_app_name() . '_redirect_urls')) {
                $redirect_urls = $cached_urls;
            }

            // 3.最后随机提取一个可用的
            $redirect_url = array_random($redirect_urls);

            // 最后跳转
            if ($domain_match && $redirect_url) {
                //兼容影片邀请二维码
                if (str_contains($request->path(), 'movie/')) {
                    $movie_path   = str_replace("/movie/", "movie/", $request->path());
                    $redirect_url = $redirect_url . "?" . $movie_path; //cosweb的iframe处理了内嵌pwa的路由
                }
                return redirect()->to($redirect_url);
            }
        }
        return $next($request);
    }
}
