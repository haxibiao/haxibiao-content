<div class="show-foot clearfix">
	@if($article->collection)	
  		<a class="notebook" href="/collection/{{ $article->collection->id }}"><i class="iconfont icon-wenji"></i><span>{{ $article->collection->name }}</span></a>
  	@endif
	<div class="copyright" data-toggle="tooltip" data-html="true" data-original-title="转载请联系作者获得授权，并标注“{{ seo_site_name() }}作者”。">
        © 著作权归作者 {{$article->user->name}} 所有
      </div>
      <div class="report">
        <a data-target=".modal-report" data-toggle="modal">举报文章</a>
      </div>
</div>