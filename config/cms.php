<?php
/**
 * cms配置(含seo)
 */
return [

    'enable'          => [
        //流量分析(seo,爬虫)
        'traffic' => env('ENABLE_TRAFFIC', false),
        //是否站群(原multi_domains)
        'sites'   => env('ENABLE_SITES', env('MULTI_DOMAINS', false)),
    ],

    //SEO域名分库模式(顶级域名)
    'sites'           => [
        // 'domain.com' => [
        //     'app_name' => 'xxyy',
        //     'app_name_cn'=>'xx影院'
        // ]
    ],

    //apps分库模式(二级域名)
    'apps'            => [
        // 'app1.domain.com' => [
        //     'app_name'    => 'xxyyapp',
        //     'app_name_cn' => 'xx影院app',
        //     'app_slogan'  => '想看的这里都有',
        // ],
    ],

    //二维码流量的防拦截处理
    'qrcode_traffic'  => [
        'scan_domain' => null, //强制单独二维码入口域名权重最高
        'redirect_urls' => [], //防护跳转地址

        'scan_domains' => [ //支持站群多个域名扫码流量合并到一个二维码
            'scan_domain1.com' => [
                'seo_domains'   => [],
                'redirect_urls' => [], //入口域名覆盖单独的跳转地址
            ],
        ],
    ],

    //SEO主题配置
    'themes'          => [
        'zaixianmeiju' => '在线美剧',
    ],

    //PWA主题配置(app群需要删除app层cssjs避免404不进入laravel)
    'pwa_themes'      => [
        'sub1.domain.com' => 'theme1',
    ],

    //实名备案信息,配置到项目里，方便备案网站nova输入时选择模板
    'icp'             => [
        '公司名' => [
            'copyright'          => 'Copyright ©2018-2021 公司名 All Rights Reserved',
            'record_code'        => '粤ICP备******号',
            'police_code'        => '粤公网安备 ********号',
            'police_code_number' => '********',
        ],
    ],

    //站群友情链接
    'friend_links'    => [
        // [
        //     'url'  => 'https://example2.com/',
        //     'name' => 'XX视频',
        // ],
    ],

    //matomo ids
    'matomo_ids'      => [
        'domain1.com' => 'siteid1',
    ],

    //站群腾讯统计ids
    'tencent_app_ids' => [
        'domain.com' => 'xxx',
    ],

    //百度统计ids
    'baidu_tj_ids'    => [],

    //google统计
    'google_tj_ids'   => [],

    // App下载页面
    'app_download_page_url' => env('CMS_APP_DOWNLOAD_PAGE_URL','/app'),
    'app_download_url'      => env('CMS_APP_DOWNLOAD_URL','/app'),
];
