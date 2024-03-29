@extends('layouts.app')

@section('title') {{ $article->subject }} - @endsection
@section('keywords') {{ $article->keywords }} @endsection
@section('description') {{ $article->summary }} @endsection

@push('seo_og_result')
    <meta property="og:type" content="{{ $article->type }}" />
    <meta property="og:url" content="https://{{ get_domain() }}/article/{{ $article->id }}" />
    <meta property="og:title" content="{{ $article->subject }}" />
    <meta property="og:description" content="{{ $article->summary }}" />
    <meta property="og:image" content="{{ $article->cover }}" />
    <meta name="weibo: article:create_at" content="{{ $article->created_at }}" />
    <meta name="weibo: article:update_at" content="{{ $article->updated_at }}" />
@endpush

@section('content')

    <div id="detail">
        <div class="main">
            <article>
                <h1>{{ $article->subject }}</h1>
                {{-- 作者 --}}
                @include('article.parts.author')
                {{-- 内容 --}}
                <div class="show-content">
                    {{-- <p class="lead">
						{{ $article->summary }}
					</p> --}}
                    <p>@include('article.parts.body')</p>

                    {{-- 视频播放 --}}
                    @if ($video = $article->video)
                        <div class="h5-player">
                            <dplayer style="height: 500px;" source="{{ $video->url }}" />
                        </div>
                    @endif

                    {{-- 动态的图片 --}}
                    @if ($article->type == 'post')
                        @foreach ($article->images as $image)
                            <img alt="{{ $image->title }}" src="{{ $image->url }}" />
                        @endforeach
                    @endif

                    @if (!empty($article->source_url))
                        <p class="pull-right small"><a href="{{ $article->source_url }}">原文链接</a></p>
                    @endif

                </div>
                {{-- 底部注释 --}}
                @include('article.parts.foot')

                {{-- 作者文章翻页 --}}
                {{-- <div class="page-turning">
            <div class="prev">
              <a href="/article/{{ $article->id }}">上一篇:《{{ $article->subject }}》</a>
			</div>
			<div class="next">
				<a href="/article/{{ $article->id }}">下一篇:《{{ $article->subject }}》</a>
			</div>
			</div> --}}
                {{-- 底部作者信息 --}}
                @include('article.parts.follow_card')
                {{-- 支持作者 --}}
                <div class="support-author">
                    <p>{{ $article->user->tip_words ? $article->user->tip_words : '如果觉得我的文章对您有用，请随意赞赏。您的支持将鼓励我继续创作！' }}
                    </p>

                    @if ($article->user && $article->user->enable_tips)
                        <a class="btn-base btn-theme" data-target=".modal-admire" data-toggle="modal">赞赏支持</a>
                        <modal-admire article-id="{{ $article->id }}"></modal-admire>
                    @endif

                    {{-- 赞赏用户 --}}
                    @include('article.parts.supporters')
                </div>


                {{-- 来自影片 --}}
                @if ($article->movie)
                    @include('video.parts.movie_item', ['movie'=>$article->movie])
                @endif

                {{-- 喜欢和分享 --}}
                <div class="mate-bottom clear">
                    <like id="{{ $article->id }}" type="article" is-login="{{ Auth::check() }}"></like>

                    <div class="share-circle">
                        <a data-action="weixin-share" data-toggle="tooltip" data-toggle="tooltip" data-placement="top"
                            title="分享到微信"><i class="iconfont icon-weixin1 weixin"></i></a>
                        <a href="javascript:void((function(s,d,e,r,l,p,t,z,c){var%20f='http://v.t.sina.com.cn/share/share.php?appkey=1881139527',u=z||d.location,p=['&amp;url=',e(u),'&amp;title=',e(t||d.title),'&amp;source=',e(r),'&amp;sourceUrl=',e(l),'&amp;content=',c||'gb2312','&amp;pic=',e(p||'')].join('');function%20a(){if(!window.open([f,p].join(''),'mb',['toolbar=0,status=0,resizable=1,width=440,height=430,left=',(s.width-440)/2,',top=',(s.height-430)/2].join('')))u.href=[f,p].join('');};if(/Firefox/.test(navigator.userAgent))setTimeout(a,0);else%20a();})(screen,document,encodeURIComponent,'','','', '推荐 @ {{ $article->user->name }} 的文章《{{ $article->subject }}》（ 分享自 {{ seo_site_name() }} ）','{{ url('/article/' . $article->id) }}?source=weibo','页面编码gb2312|utf-8默认gb2312'));"
                            data-toggle="tooltip" data-placement="top" title="分享到微博"><i
                                class="iconfont icon-sina weibo"></i></a>
                        {{-- <a data-toggle="tooltip" data-placement="top" title="下载微博长图片"><i class="iconfont icon-zhaopian other"></i></a> --}}
                    </div>
                </div>
                {{-- 评论中心 --}}
                {{-- <comments type="articles" id="{{ $article->id }}" author-id="{{ $article->user_id }}"
                    comment-replies={{ $article->count_comments ?: 0 }}>
                </comments> --}}
                <comment-module type="articles" id="{{ $article->id }}" author-id="{{ $article->user_id }}"
                    count-comments={{ $article->count_comments ?: 0 }} />
            </article>
        </div>
    </div>
@endsection

@push('section')
    {{-- 底部内容 --}}
    <div class="note-bottom">
        <div class="container">
            <div class="row clearfix">
                <div class="col-sm-offset-2 col-sm-8">
                    <div class="bottom-title"><span>被以下专题收入，发现更多相似内容</span></div>

                    <div class="recommend-category">
                        <a data-target=".modal-category-contribute" data-toggle="modal" class="category-label">
                            <span class="name">＋ 收入我的专题</span>
                        </a>
                        @foreach ($article->categories->unique() as $category)
                            <a href="/category/{{ $category->id }}" class="category-label"
                                title="{{ $category->id }}:{{ $category->name }}">
                                <img src="{{ $category->iconUrl }}"
                                    alt="{{ $category->id }}:{{ $category->name }}">
                                <span class="name">{{ $category->name }}</span>
                            </a>
                        @endforeach
                    </div>

                    <div class="bottom-title">
                        <span>推荐阅读</span>
                        <a href="/" class="right">
                            更多精彩内容<i class="iconfont icon-youbian"></i>
                        </a>
                    </div>

                    <ul class="article-list">
                        {{-- 文章 --}}
                        @each('parts.article_item', $data['recommended'], 'article')
                    </ul>

                </div>
            </div>
        </div>
    </div>
@endpush

@push('side_tool')
    <side-tool id="{{ $article->id }}" is-self="{{ $article->isSelf() }}" is-login="{{ Auth::check() }}">
        <share placement='left' url="{{ url('/article/' . $article->id) }}" article_id="{{ $article->id }}"
            author="{{ $article->user->name }}" title="{{ $article->subject }}"></share>
    </side-tool>
@endpush

@push('modals')
    @if (Auth::check())
        <modal-add-category article-id="{{ $article->id }}"></modal-add-category>
        <modal-category-contribute article-id="{{ $article->id }}"></modal-category-contribute>
    @endif
    {{-- 分享到微信 --}}
    {{-- <modal-share-wx url="{{ url()->full() }}" aid="{{ $article->id }}"></modal-share-wx> --}}
    {{-- 举报 --}}
    <modal-report></modal-report>
@endpush

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
