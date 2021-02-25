<?php

namespace Haxibiao\Content\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class SitemapController extends Controller
{
    /**
     * 检查并生成缺失的地图
     *
     */
    public function index()
    {
        $siteMapContentExist = Storage::disk('public')->exists('sitemap/' . get_domain() . '/sitemap.xml');
        if (!$siteMapContentExist) {
            Artisan::call('sitemap:generate', ['--domain' => get_domain()]);
        }
        $siteMapContentPath = Storage::disk('public')->get('sitemap/' . get_domain() . '/sitemap.xml');
        if (!$siteMapContentPath) {
            abort(404);
        }
        return response($siteMapContentPath)
            ->header('Content-Type', 'text/xml');
    }

    /**
     * 返回当前域名的站点地图
     *
     * @param string $name_en
     */
    public function name_en($name_en)
    {
        // XML 格式
        $endWithXml     = ends_with($name_en, '.xml');
        $relativePath   = 'sitemap/' . get_domain() . '/' . $name_en;

        $existsSiteMapContent = Storage::disk('public')->exists($relativePath);
        if (!$existsSiteMapContent) {
            abort(404);
        }

        if ($endWithXml) {
            $siteMapContent = Storage::disk('public')->get($relativePath);
            return response($siteMapContent)
                ->header('Content-Type', 'text/xml');
        }
        // GZ 下载
        $siteMapContent = Storage::disk('public')->get($relativePath);

        return response($siteMapContent)
            ->header('Content-Type', 'application/octet-stream');
    }
}
