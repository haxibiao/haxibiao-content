@php
$post = $video->post;
if (!$post) {
    $post = $video->article;
    $collection = null;
} else {
    $collection = $post->collection;
}

@endphp

{{-- 短视频动态时代的，关联动态+合集为主 --}}

@extends('layouts.video')

@section('title')
    {{ $video->title ?? $post->description }} -
@stop

@push('seo_og_result')
    @if ($video->post)
        <meta property="og:type" content="video" />
        <meta property="og:url" content="https://{{ get_domain() }}/video/{{ $video->id }}" />
        <meta property="og:title" content="{{ $post->description }}" />
        <meta property="og:description" content="{{ $post->description }}" />
        <meta property="og:image" content="{{ $video->cover }}" />
        <meta name="weibo: article:create_at" content="{{ $post->created_at }}" />
        <meta name="weibo: article:update_at" content="{{ $post->updated_at }}" />
    @endif
@endpush

@section('content')
    <div class="player-container">

        <div class="playerBox">
            <div class="author-info">
                @include('video.parts.author')
            </div>
            <div class="player-basic clearfix">
                <div class="playerArea col-sm-8">
                    <div class="h5-player">
                        {{-- <div class="embed-responsive embed-responsive-16by9">
                            <video controls="" poster="{{ $video->cover }}" preload="auto" autoplay="true">
                                <source src="{{ $video->url }}" type="{{ $video->isHls ? 'application/x-mpegURL' : 'video/mp4' }}">
                                </source>
                            </video>
                        </div> --}}
                        <dplayer style="height: 500px;" source="{{ $video->url }}" />
                    </div>
                    <div class="video-body">
                        @if ($collection)
                            <a href="/collection/{{ $collection->id }}" class="category-name"
                                title="{{ $collection->id }}:{{ $collection->name }}">
                                <span class="name"> {{ '#' . $collection->name }} </span>
                            </a>
                        @endif
                        <span class="content">
                            {{ $post->description }}
                        </span>
                    </div>
                    <div class="h5-option">
                        <video-like id="{{ $post->id }}" type="posts" is-login="{{ Auth::check() }}">
                        </video-like>

                        <div class="comments">
                            <to-comment comment-replies={{ $post->count_replies ?? 0 }}></to-comment>
                        </div>
                        {{-- <div class="share">
                            <share-module></share-module>
                        </div> --}}
                        {{-- @include('video.parts.share') --}}
                    </div>
                    <div class="pc-option">
                        @if (!$post->isSelf())
                            @if ($post->user && $post->user->enable_tips)
                                <a class="btn btn-warning" data-target=".modal-admire" data-toggle="modal">赞赏支持</a>
                                <modal-admire article-id="{{ $post->id }}"></modal-admire>
                            @endif
                        @endif
                        <like id="{{ $post->id }}" type="posts" is-login="{{ Auth::check() }}"></like>

                        @include('video.parts.share')
                    </div>
                </div>
                <div class="video-right">
                    <div class="listArea">
                        {{-- 同作者视频 --}}
                        <authors-video user-id="{{ $video->user_id }}" num="4" video-id="{{ $video->id }}"
                            related-page="{{ $data['related_page'] }}"></authors-video>
                    </div>
                </div>
            </div>
            <div class="video-title">
                @if ($collection)
                    <a href="/collection/{{ $collection->id }}" class="category-name"
                        title="{{ $collection->id }}:{{ $collection->name }}">
                        <span class="name"> {{ '#' . $collection->name }} </span>
                    </a>
                @endif
                配文：{{ $post->description }}
                <div class="video-info">
                    @if (!empty($post->category))
                        <a href="/category/{{ $post->category->id }}" class="category-name">专题:
                            {{ $post->category->name }}</a>
                    @endif
                </div>
            </div>
            <div class="video-relevant">
                <div class="author-info">
                    @include('video.parts.author')
                    {{-- <div class="admire">
                        @if (!$post->isSelf())
                            @if ($post->user && $post->user->enable_tips)
                                <a class="btn-base btn-theme" data-target=".modal-admire" data-toggle="modal">赞赏支持</a>
                                <modal-admire article-id="{{ $post->id }}"></modal-admire>
                            @endif
                        @endif
                    </div> --}}
                </div>
                <authors-video user-id="{{ $video->user_id }}" video-id="{{ $video->id }}"></authors-video>
                @if ($collection)
                    <div class="video-categories" style="margin-top:20px">
                        <h4>来自合集：</h4>
                        @include('video.parts.collection_item',['collection' => $post->collection])
                    </div>
                @endif
            </div>
            {{-- <div class="video-info">
                @if (!empty($collection))
                    <a href="/category/{{ $collection->id }}" class="category-name">{{ $collection->name }}</a>
                @endif
                <i class="iconfont icon-shijian"></i>
                <span>发布于：{{ $video->createdAt() }}</span>
                @if (show_hits())
                <i class="iconfont icon-shipin1"></i>
                <span class="hits">{{ $post->hits??0 }}次播放</span>
                @endif
            </div> --}}
        </div>

        <div class="sectionBox">
            <div class="container clearfix">
                <div class="row">
                    <div class="col-md-8">
                        {{-- 评论中心 --}}
                        <comments comment-replies={{ $post->count_replies ?? 0 }} type="posts" id="{{ $post->id }}"
                            author-id="{{ $video->user_id }}"></comments>
                    </div>
                    <div class="col-md-4">
                        <div class="guess-like">
                        </div>

                        {{-- 来自影片 --}}
                        @if ($post->movie)
                            @include('video.parts.movie_item', ['movie'=>$post->movie])
                        @endif
                        {{-- 同合集视频 --}}
                        <authors-video collection-id="{{ $post->collection_id }}" video-id="{{ $video->id }}"
                            related-page="{{ $data['related_page'] }}"></authors-video>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <div class="share-module">
        <div class="module-share-h3">分享到....</div>
        <div>@include('video.parts.share', ['subject' => $post->description, 'url'=>url('/video/'.$video->id)])</div>
        <close-share></close-share>
    </div>
    <div id="pageLayout">

    </div>
@stop

@push('js')
    @if (Auth::check())
        <script type="text/javascript">
            var at_config = {
                at: "@",
                data: window.tokenize('/api/related-users'),
                insertTpl: '<span data-id="${id}">@${name}</span>',
                displayTpl: "<li > ${name} </li>",
                limit: 200
            }
            $('#editComment').atwho(at_config); // 初始化
        </script>
    @endif
@endpush

@push('modals')
    {{-- 分享到微信 --}}
    {{-- <modal-share-wx url="{{ url()->full() }}" aid="{{ $post->video_id }}"></modal-share-wx> --}}
@endpush
