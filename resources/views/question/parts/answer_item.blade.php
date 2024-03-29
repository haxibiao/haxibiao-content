<div class="answer-item">
    <div class="answer-user">
        <div class="note-info">
            <a href="/user/{{ $answer->user->id }}" class="avatar">
                <img src="{{ $answer->user->avatarUrl }}" alt=""></a>

            @if (!$answer->isSelf())
                <follow type="user" id="{{ $answer->user->id }}" user-id="{{ user_id() }}"
                    followed="{{ is_follow('user', $answer->user->id) }}">
                </follow>
            @endif

            <div class="title">
                @if ($answer->bonus && $answer->bonus > 0)
                    <div class="pull-right">
                        <span class="red">已获得奖金￥{{ $answer->bonus }}</span>
                    </div>
                @endif
                <a href="/user/{{ $answer->user->id }}" class="name">{{ $answer->user->name }}</a>
                <img class="badge-icon" src="/images/verified.png" data-toggle="tooltip" data-placement="top"
                    title="{{ seo_site_name() }}认证" alt="">
                <span
                    class="user-intro">{{ $answer->user->question_tag ? $answer->user->question_tag : seo_site_name() . '热心用户' }}</span>
            </div>
            <div class="info">
                <p>{{ $answer->timeAgo() }}</p>
            </div>
        </div>
    </div>
    <div class="answer-text fold">
        <div class="answer-text-full">
            {!! $answer->answer !!}
            @if ($answer->article)
                {!! $answer->article->parsedBody() !!}
            @endif
        </div>
        <a href="javascript:;" class="expand-bottom">展开全部</a>
    </div>

    @if (Auth::check())
        <answer-tool answer-id="{{ $answer->id }}" closed="{{ $question->closed ? true : false }}"
            is-self="{{ $answer->isSelf() }}" is-payer="{{ $question->bonus && $question->isSelf() }}"
            url="{{ url('/question/' . $question->id) }}" author="{{ $answer->user->name }}"
            title="{{ $question->title }}"></answer-tool>
    @endif
</div>
@push('js')
    <script>
        $(function() {
            $('.answer-item').each(function(index, el) {
                if ($(el).height() < 300) {
                    $(el).find('.answer-text').removeClass('fold');
                };
            });
            $('.answer-item .expand-bottom').on('click', function() {
                $(this).parent('.answer-text').removeClass('fold');
            })
        });
    </script>
@endpush
