<div class="atten-people distance">
	<p class="plate-title">关注的人</p>
    <ul class="list-people"> 
        
    	@foreach($category->topFollowers() as $user) 
    	 <li>
			<a class="avatar" href="/user/{{ $user->id }}" data-toggle="tooltip" data-placement="bottom" title="{{ $user->name }}">
				<img src="{{ $user->avatarUrl }}" alt="">
			</a>
		</li>
    	@endforeach

	    @if($category->follows()->count() > 8)
	        <a class="more iconfont icon-gengduo"></a>
        @endif
    </ul>
</div>