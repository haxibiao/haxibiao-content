@extends('layouts.app')

@section('title') 搜索视频 {{ get_kw() }} @endsection

@section('content')
    <div id="search-content" class="articles">
        <section class="left-aside clearfix">
            @include('search.aside')
            <div class="main">
                <div class="search-content">
                    <div class="plate-title">
                        <span>综合排序</span>
                        <a href="javascript:;" class="right">{{ $data['video']->total() }} 个结果</a>
                    </div>
                    <div class="note-list">
                        @foreach ($data['video'] as $article)
                            <li class="article-item {{ $article->cover ? 'have-img' : '' }}">
                                @if ($article->cover)
                                    <a class="wrap-img" href="{{ $article->url }}">
                                        <img src="{{ $article->cover }}" alt="">
                                    </a>
                                @endif
                                <div class="content">
                                    <div class="author">
                                        <a class="avatar" href="/user/{{ $article->user_id }}">
                                            <img src="{{ $article->user->avatarUrl }}" alt="">
                                        </a>
                                        <div class="info">
                                            <a class="nickname"
                                                href="/user/{{ $article->user_id }}">{{ $article->user->name }}</a>
                                            @if ($article->user->is_signed)
                                                <img class="badge-icon" src="/images/signed.png" data-toggle="tooltip"
                                                    data-placement="top" title="{{ config('app.name') }}签约作者" alt="">
                                            @endif
                                            @if ($article->user->is_editor)
                                                <img class="badge-icon" src="/images/editor.png" data-toggle="tooltip"
                                                    data-placement="top" title="{{ config('app.name') }}小编" alt="">
                                            @endif
                                            <span class="time"
                                                data-shared-at="{{ $article->created_at }}">{{ $article->timeAgo() }}</span>
                                        </div>
                                    </div>
                                    <a class="title" href="{{ $article->url }}">
                                        <span>{!! $article->subject !!}</span>
                                    </a>
                                    <p class="abstract">
                                        {!! $article->description !!}
                                    </p>
                                    <div class="meta">
                                        <a href="{{ $article->url }}">
                                            <i class="iconfont icon-liulan"></i> {{ $article->hits }}
                                        </a>
                                        <a href="{{ $article->url }}">
                                            <i class="iconfont icon-svg37"></i> {{ $article->count_replies }}
                                        </a>
                                        <span><i class="iconfont icon-03xihuan"></i> {{ $article->count_likes }}</span>
                                        @if ($article->count_tips)
                                            <span><i class="iconfont icon-qianqianqian"></i>
                                                {{ $article->count_tips }}</span>
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </div>
                    @if (!$data['video']->total())
                        <blank-content></blank-content>
                    @endif
                    {!! $data['video']->appends(['q' => $data['query']])->render() !!}
                </div>
            </div>
        </section>
    </div>
@endsection
