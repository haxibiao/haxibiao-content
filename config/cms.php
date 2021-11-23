<?php
/**
 * cms配置(含seo)
 */
return [
    //SEO域名分库模式(顶级域名)
    'sites'              => [
        // 'domain.com' => [
        //     'app_name' => 'xxyy',
        //     'db_name' => 'xxyy',
        //     'app_name_cn'=>'xx影院'
        // ]
    ],

    //apps分库模式(二级域名)
    'apps'               => [
        // 'app1.domain.com' => [
        //     'app_name'    => 'xxyyapp',
        //     'db_name'    => 'xxyyapp',
        //     'app_name_cn' => 'xx影院app',
        // ],
    ],

    //默认不开启cms的seo流量分析
    'enable_traffic'     => env('ENABLE_TRAFFIC', false),

    //针对腾讯流量的防拦截处理
    'tencent_traffic'    => [
        'income_domain' => null, //配置了就覆盖二维码入口域名
        'income_domains' => [
            'xxx.com' => [], //支持多个入口域名覆盖跳转redirect_urls配置
        ],
        'redirect_urls' => [],
    ],

    'enable_pwa_domains' => [],

    //是否站群(原multi_domains)
    'enable_sites'       => env('ENABLE_SITES', env('MULTI_DOMAINS', false)),

    //SEO主题配置
    'themes'             => [
        'zaixianmeiju' => '在线美剧',
    ],

    //PWA主题配置(app群需要删除app层cssjs避免404不进入laravel)
    'pwa_themes'         => [
        'sub1.domain.com' => 'theme1',
    ],

    //实名备案信息,配置到项目里，方便备案网站nova输入时选择模板
    'icp'                => [
        '公司名' => [
            'copyright'          => 'Copyright ©2018-2021 公司名 All Rights Reserved',
            'record_code'        => '粤ICP备******号',
            'police_code'        => '粤公网安备 ********号',
            'police_code_number' => '********',
        ],
    ],

    //站群友情链接
    'friend_links'       => [
        // [
        //     'url'  => 'https://example2.com/',
        //     'name' => 'XX视频',
        // ],
    ],

    //matomo ids
    'matomo_ids'         => [
        'domain1.com' => 'siteid1',
    ],

    //站群腾讯统计ids
    'tencent_app_ids'    => [
        'domain.com' => 'xxx',
    ],

    //百度统计ids
    'baidu_tj_ids'       => [],

    //google统计
    'google_tj_ids'      => [],
];
