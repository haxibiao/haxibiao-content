@extends('layouts.app')

@section('title') 搜索专题 {{ get_kw() }} @endsection

@section('content')
    <div id="search-content">
        <section class="left-aside clearfix">
            @include('search.aside')
            <div class="main">
                <div class="search-content">
                    <div class="plate-title">
                        <span>综合排序</span>
                        <a href="javascript:;" class="right">{{ $data['categories']->total() }} 个结果</a>
                    </div>
                    <div class="note-list">
                        @foreach ($data['categories'] as $category)
                            <li class="note-info"><a href="/category/{{ $category->id }}" class="avatar-category">
                                    <img src="{{ $category->logo }}" alt=""></a>
                                <follow type="categories" id="{{ $category->id }}" user-id="{{ user_id() }}"
                                    followed="{{ is_follow('categories', $category->id) }}">
                                </follow>
                                <div class="title"><a href="/category/{{ $category->id }}"
                                        class="name">{{ $category->name }}</a></div>
                                <div class="info">
                                    <p>收录了{{ $category->count_articles }}篇作品，{{ $category->count_follows }}人关注
                                    </p>
                                </div>
                            </li>
                        @endforeach
                    </div>
                    @if (!$data['categories']->total())
                        <blank-content></blank-content>
                    @endif
                    {!! $data['categories']->links() !!}
                </div>
            </div>
        </section>
    </div>
@endsection
