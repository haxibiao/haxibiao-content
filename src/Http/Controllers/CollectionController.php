<?php

namespace Haxibiao\Content\Http\Controllers;

use Haxibiao\Content\Collection;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CollectionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $collection       = \App\Collection::with(['user', 'followables'])->findOrFail($id);
        $data['posts']    = $collection->posts()->latest('id')->paginate(10);
        $data['articles'] = $collection->publishedArticles()->latest('id')->paginate(10);
        return view('collection.show')
            ->with('collection', $collection)
            ->with('data', $data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    //分享合集
    public function shareCollection($id)
    {
        $collection = Collection::find($id);
        if (empty($collection)) {
            return view(
                'errors.404',
                ['data' => "分享的合集好像不存在呢(。・＿・。)ﾉ"]
            );
        }
        $posts = $collection->posts()->has('video')->latest()->take(10)->get();
        // return $posts;
        return view('share.collect', ['collection' => $collection, 'posts' => $posts]);
    }
}
