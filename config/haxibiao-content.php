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
    ],

];
