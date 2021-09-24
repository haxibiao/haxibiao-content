@extends('layouts.app')

@section('title')
	视频主页 -
@stop
@section('keywords') 视频主页,{{ get_seo_keywords() }} @endsection
@section('description') 视频主页, {{ get_seo_description() }} @endsection

@section('content')
<div class="container">
    {{-- 抖音合集 --}}
    @include('video.parts.top_collections')
    @if(config('media.movie.enable',false))
        {{-- 最新电影 --}}
        @include('video.parts.latest_movies')
    @endif
{{--    <div class="vd-head">--}}
{{--      <h3 class="vd-title">--}}
{{--        <span class="title-icon">--}}
{{--          <i class="iconfont icon-huo"></i>视频专题--}}
{{--        </span>--}}
{{--      </h3>--}}
{{--    </div>--}}
{{--    @each('video.parts.hot_category_articles', $videos, "articles")--}}

    <div class="vd-head">
          <h3 class="vd-title">
            <span class="title-icon">
              <i class="iconfont icon-huo"></i>电影图解
            </span>
          </h3>
    </div>
    @include('video.parts.hot_category_articles')

    <video-list api="api/getlatestVideo" is-desktop="{{ isDeskTop() == 1 }}" start-page="1"></video-list>
</div>
@stop

@push('scripts')
  <script>
   	$(".cateory-logo").on('error', function(){
    	$(this).attr("src", "/images/default_logo.png");
    });
  </script>
@endpush
