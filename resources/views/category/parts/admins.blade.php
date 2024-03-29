<div class="administrator distance">
	<p class="plate-title">管理员</p>
	<ul>
	    @foreach($category->top_admins as $admin)
	    	<li class="author">
				<a href="/user/{{ $admin->id }}" class="avatar">
					<img src="{{ $admin->avatarUrl }}" alt=""></a>
				<a href="/user/{{ $admin->id }}" class="info">{{ $admin->name }}</a>
				@if($category->isCreator($admin))
					<span class="extrude">创建者</span>
				@endif
			</li>
	    @endforeach
	</ul>
	@if($category->admins()->count() > 10)
	<a href="javascript:;" class="open iconfont icon-xia">展开</a>
	@endif
</div>
