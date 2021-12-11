<?php

declare (strict_types = 1);

return [
    'enable'                  => [
        //内容热度
        'hits'        => false,
        //答题模块
        'question'    => env('ENABLE_QUESTION', true),
        //哈希云
        'haxiyun'     => env('ENABLE_HAXIYUN', false),
        //无水印视频分享
        'video_share' => env('ENABLED_VIDEO_SHARE', false),
    ],

    // 分享模版
    'share_config'            => [
        'share_msg'            => '%s/share/post/%d?s= #%s#,打开【%s】,直接观看视频,玩视频就能赚钱~,',
        'share_collection_msg' => '%s/share/collection/%d?s= #%s#,打开【%s】,直接观看视频合集,玩视频就能赚钱~,',
    ],

    // 动态是否开启马甲号分发
    'post_open_vest'          => env('POST_OPEN_VEST', false),

    // 马甲号的动态是否开启合集
    'post_open_collection'    => env('POST_OPEN_COLLECTION', true),

    // 合集默认封面图片
    'collection_default_logo' => 'http://haxibiao-1251052432.cos.ap-guangzhou.myqcloud.com/images/collection.png',

    // 超过这个大小的视频不参与视频分享 100M=50*1024*1024
    'video_threshold_size'    => env('VIDEO_THRESHOLD_SIZE', 100 * 1024 * 1024),

    /**
     * 专题模块配置
     */
    'category'                => [
        'middleware' => [
            'web',
        ],
    ],
    /**
     * 文章模块配置
     */
    'article'                 => [
        'middleware' => [
            'web',
        ],
    ],
];
