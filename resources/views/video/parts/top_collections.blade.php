 <div class="video-top">
 	  <div class="vd-head">
        <h3 class="vd-title">
          <span class="title-icon">
            <i class="iconfont icon-huo"></i>最新合集
          </span>
        </h3>
    </div>
    <ul class="category-video-list">
      @foreach($collections as $collection)
    	<li class="category-video-item">
    		<a href="/collection/{{$collection->id}}">
            	<img class="game-category" src="{{$collection->logo}}" alt="{{$collection->name}}">
            	<p>{{$collection->name}}</p>
        	</a>
    	</li>
      @endforeach
	  </ul>
</div>
