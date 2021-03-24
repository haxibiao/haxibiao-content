@if($post)
    <li class="content-item {{ $post->cover ? 'have-img' : '' }}">
      @if($post->cover)
        <a class="wrap-img" href="/post/{{ $post->id }}"    >
            <img src="{{ $post->cover }}" alt="{{ $post->description ?? $post->body }}">

            @if($post->video)
            <span class="rotate-play">
              <i class="iconfont icon-shipin"></i>
            </span>
            <i class="duration">@sectominute($post->video->duration)</i>
            @endif

        </a>
      @endif
      <div class="content">
        @if( $post->user )
        <div class="author">
          <a class="avatar"   href="/user/{{ $post->user->id }}">
            <img src="{{ $post->user->avatarUrl }}" alt="">
          </a>
          <div class="info">
            <a class="nickname"   href="/user/{{ $post->user->id }}">{{ $post->user->name }}</a>
            @if($post->user->is_signed)
              <img class="badge-icon" src="/images/signed.png" data-toggle="tooltip" data-placement="top" title="{{ seo_site_name() }}签约作者" alt="">
            @endif
            @if($post->user->is_editor)
              <img class="badge-icon" src="/images/editor.png" data-toggle="tooltip" data-placement="top" title="{{ seo_site_name() }}小编" alt="">
            @endif
            <span class="time">{{ $post->updatedAt() }}</span>
          </div>
        </div>
        @endif

        {{-- 然后任何类型，这段简介是一定要显示的 --}}
        <a class="abstract"   href="/post/{{ $post->id }}">
          {{ $post->description ?? $post->body }}
        </a>

        <div class="meta">
          @if($post->category)
            <a class="category"   href="/category/{{ $post->category->id }}">
              <i class="iconfont icon-zhuanti1"></i>
              {{ $post->category->name }}
            </a>
          @endif

          <a class="nickname"   href="/user/{{ $post->user->id }}">{{ $post->user->name }}</a>
          <a   href="/post/{{ $post->id }}">
            <i class="iconfont icon-liulan"></i> {{ $post->hits }}
          </a>

          <a   href="/post/{{ $post->id }}/#comments">
            <i class="iconfont icon-svg37"></i> {{ rand(1,10) }}
          </a>
          <span><i class="iconfont icon-03xihuan"></i> {{ rand(0,3) }} </span>
        </div>
      </div>
    </li>
@endif
