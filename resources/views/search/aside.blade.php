<div class="aside">
    <div>
        <ul class="aside-menu">
            <li>
                <a href="/search{{ '?q=' . get_kw() }}">
                    <div class="icon-wrp"><i class="iconfont icon-icon_article"></i></div> <span>文章</span>
                </a>
            </li>
            @if (config('media.enable.movie', false))
                <li>
                    <a href="/search/movies{{ '?q=' . get_kw() }}">
                        <div class="icon-wrp"><i class="iconfont icon-shipin1"></i></div> <span>电影</span>
                    </a>
                </li>
            @endif
            <li>
                <a href="/search/video{{ '?q=' . get_kw() }}">
                    <div class="icon-wrp"><i class="iconfont icon-shipin"></i></div> <span>视频</span>
                </a>
            </li>
            <li>
                <a href="/search/users{{ '?q=' . get_kw() }}">
                    <div class="icon-wrp"><i class="iconfont icon-yonghu01"></i></div> <span>用户</span>
                </a>
            </li>
            <li>
                <a href="/search/categories{{ '?q=' . get_kw() }}">
                    <div class="icon-wrp"><i class="iconfont icon-zhuanti1"></i></div> <span>专题</span>
                </a>
            </li>
            <li>
                <a href="/search/collections{{ '?q=' . get_kw() }}">
                    <div class="icon-wrp"><i class="iconfont icon-wenji"></i></div> <span>文集</span>
                </a>
            </li>
        </ul>
    </div>

    <hot-search class="hidden-xs"></hot-search>

    {{-- 未登录用户不显示最近搜索管理 --}}
    @if (Auth::check())
        <recently class="hidden-xs"></recently>
    @endif
</div>
