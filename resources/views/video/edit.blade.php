@php
    $article = $video->article;
@endphp

@extends('layouts.app')

@section('title')
	编辑视频动态 - {{ $video->id }}
@stop
@php
    //编辑成功返回之前的页面
    session()->put('url.intended', request()->headers->get('referer'));
@endphp

@section('content')
<div class="container">
      <ol class="breadcrumb">
        <li><a href="/">{{ seo_site_name() }}</a></li>
        <li><a href="/video">视频</a></li>
        <li><a href="/video/{{ $video->id }}">{{ $video->title }}</a></li>
      </ol>

    {!! Form::open(['method' => 'PUT', 'route' => ['video.update', $video->id], 'class' => 'form-horizontal', 'enctype' => 'multipart/form-data']) !!}
    <div class="panel panel-default">
        <div class="panel-heading">

            <div class="btn-group pull-right">
                {!! Form::submit("修改", ['class' => 'btn btn-primary']) !!}
            </div>
            <h3 class="panel-title" style="height: 40px">
                编辑视频动态
            </h3>
        </div>
        <div class="panel-body">
            <div class="col-md-10 col-md-offset-1">

                <div class="form-group{{ $errors->has('title') ? ' has-error' : '' }}">
                    {!! Form::label('title', '标题(非必填)') !!}
					{!! Form::text('title', $video->article->subject, ['class' => 'form-control']) !!}
                    <small class="text-danger">
                        {{ $errors->first('title') }}
                    </small>
                </div>


                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group{{ $errors->has('categories') ? ' has-error' : '' }}">
                            {!! Form::label('categories', '专题') !!}
                            <category-select
                                categories="{{ json_encode($video->article->categories->pluck('name','id')) }}">
                            </category-select>
                            <small class="text-danger">{{ $errors->first('categories') }}</small>
                        </div>
                    </div>
                </div>

                <div class="form-group{{ $errors->has('body') ? ' has-error' : '' }}">
                    {!! Form::label('descri', '正文(必填)') !!}
					{!! Form::textarea('body', $video->article->body, ['class' => 'form-control', 'required'=>true]) !!}
                    <small class="text-danger">
                        {{ $errors->first('body') }}
                    </small>
                </div>


                {{-- <div class="form-group{{ $errors->has('video') ? ' has-error' : '' }}">
                    {!! Form::label('video', '视频文件') !!}
                            {!! Form::file('video') !!}
                    <p class="help-block">
                        (目前只支持mp4格式)
                    </p>
                    <small class="text-danger">
                        {{ $errors->first('video') }}
                    </small>
                </div> --}}

                <div class="row">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title">截图</h3>
                        </div>
                        <div class="panel-body">
                            @if(!empty($data['covers']))
                                @php
                                    $coverIndex = 0;
                                @endphp
                                @foreach($data['covers'] as $cover)
                                @php
                                    $coverIndex++;
                                @endphp
                                <div class="col-xs-6 col-md-3 {{ $errors->has('cover') ? ' has-error' : '' }}">
                                    <label for="cover{{ $coverIndex }}">
                                        <img src="{{ $cover }}" class="img img-responsive">
                                    </label>
                                    {{-- trick here, need custom replace id="**" attribute to get label for radio work --}}
                                    @php
                                        //表示图片的初始选中状态
                                        $checked = $article->cover == $cover;
                                    @endphp
                                    {!! Form::radio('cover', $cover, $checked, ['id' => $coverIndex]) !!}
                                    <label for="cover">
                                        选取
                                    </label>
                                    <small class="text-danger">{{ $errors->first('cover') }}</small>
                                </div>
                                @endforeach
                            @else
                                <request-covers api="/api/{{$video->id}}/covers"></request-covers>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="panel-footer" style="height: 50px">
                <div class="radio{{ $errors->has('status') ? ' has-error' : '' }} pull-right">
                     <label for="status0">
                         {!! Form::radio('status', 0,  $article->status == 0, ['id' => 'status0']) !!} 下架
                     </label>
                     <small class="text-danger">{{ $errors->first('status') }}</small>
                </div>
                <div class="radio{{ $errors->has('status') ? ' has-error' : '' }} pull-right">
                     <label for="status1">
                         {!! Form::radio('status', 1,  $article->status == 1, ['id' => 'status1','checked']) !!} 发布
                     </label>
                     <small class="text-danger">{{ $errors->first('status') }}</small>
                </div>
        </div>
        <p id="p1"></p>
    </div>
    {!! Form::close() !!}
</div>
@stop
