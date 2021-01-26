<?php

namespace Haxibiao\Content\Http\Controllers;

use Haxibiao\Content\Collection;
use Illuminate\Routing\Controller;

class CollectionController extends Controller
{
    //分享合集
    public function shareCollection($id)
    {
        $collection = Collection::find($id);
        if (empty($collection)) {
            return  view(
                'errors.404',
                ['data' => "分享的合集好像不存在呢(。・＿・。)ﾉ"]
            );
        }
        $posts = $collection->posts()->latest()->take(10)->get();
        // return $posts;
        return view('share.collect', ['collection' => $collection, 'posts' => $posts]);
    }
}
