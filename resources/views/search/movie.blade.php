@extends('layouts.app')

@section('title') 搜索电影 {{ get_kw() }} @endsection

@section('content')
    <div id="search-content">
        <section class="left-aside clearfix">
            @include('search.aside')
            <div class="main">
                <div class="search-content">
                    <div class="plate-title">
                        <span>综合排序</span>
                        <a href="javascript:;" class="right">{{ $data['movie']->total() }} 个结果</a>
                    </div>
                    <div class="note-list">
                        @foreach ($data['movie'] as $movie)
                            <li class="note-info"><a href="/movie/{{ $movie->id }}" class="avatar-category">
                                    <img src="{{ $movie->cover }}" alt=""></a>
                                <div class="title"><a href="/movie/{{ $movie->id }}"
                                        class="name">{{ $movie->name }}</a></div>
                                <div class="info">
                                    <p>主演:
                                        @if ($movie->actors)
                                            {{ $movie->actors }}
                                        @else
                                            未知
                                        @endif
                                    </p>
                                </div>
                            </li>
                        @endforeach
                    </div>
                    @if (!$data['movie']->total())
                        <blank-content></blank-content>
                    @endif
                    {!! $data['movie']->links() !!}
                </div>
            </div>
        </section>
    </div>
@endsection
