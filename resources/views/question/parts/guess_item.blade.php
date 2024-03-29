@php
  $have_img = !empty($question->relateImage()) ? 'have-img' : '';
@endphp
<li class="question-item simple {{ $have_img }}">
    <div class="question-warp">
        <div class="content">
           <a   href="/question/{{ $question->id }}" class="title"><span>{{ $question->title }}</span></a>
           <div class="meta">
             <span>{{ $question->count_answers }}回答</span>
           </div>
        </div>
        <a href="/question/{{ $question->id }}"   class="wrap-img"><img src="{{ $question->relateImage() }}" alt=""></a>
    </div>
</li>
