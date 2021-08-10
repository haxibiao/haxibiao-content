<?php
/**
 * cms配置开关
 */
return [
    //是否APP+不同域名分库模式(一个服务器多站/多APP切换数据库实例)
    'app_domain_switch' => [
        // env('APP_NAME') => env('APP_DOMAIN'),
        // 'app_name2' => '域名2'
    ],

    //是否多域名站群(sites表配置)+单数据库
    'multi_domains'     => env('MULTI_DOMAINS', false),

    //可选主题
    'themes'            => [
        'zaixianmeiju' => '在线美剧',
    ],

    //实名备案信息,配置到项目里，方便备案网站nova输入时选择模板
    'icp'               => [
        '公司名' => [
            'copyright'          => 'Copyright ©2018-2021 公司名 All Rights Reserved',
            'record_code'        => '粤ICP备******号',
            'police_code'        => '粤公网安备 ********号',
            'police_code_number' => '********',
        ],
    ],
];
