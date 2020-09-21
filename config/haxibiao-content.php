<?php

declare (strict_types = 1);

return [

    /*
    |--------------------------------------------------------------------------
    | Eloquent Models
    |--------------------------------------------------------------------------
     */

    'models' => [

        /*
        |--------------------------------------------------------------------------
        | Package's Content Model
        |--------------------------------------------------------------------------
         */
        'category' => Haxibiao\Content\Category::class,
        'article'  => Haxibiao\Content\Article::class,
        'post'     => Haxibiao\Content\Post::class,
        'issue'     => Haxibiao\Content\Issue::class,
        'collection'     => Haxibiao\Content\Collection::class,
    ],
    // 分享模版
    'share_config' =>[
        'share_msg' => '#%s/share/post/%d#, #%s#,打开【%s】,直接观看视频,玩视频就能赚钱~,'
    ],
    // 动态是否开启马甲号分发
    'post_open_vest' => env('POST_OPEN_VEST',false)
];
