<li class="col-sm-4 recommend-card">
  <div>
    <a   href="/category/{{ $category->id }}">
      <img class="avatar-category" src="{{ $category->logoUrl }}" alt="">
      <h4 class="name single-line">{{ $category->name }}</h4>
      <p class="category-description">{{ $category->description }}</p>
    </a>

      <follow
        type="categories"
        id="{{ $category->id }}"
        user-id="{{ user_id() }}"
        followed="{{ is_follow('categories', $category->id) }}">
      </follow>

    <hr>
    <div class="count"><a   href="/category/{{ $category->id }}">{{ $category->count }}篇作品</a> · {{ $category->count_follows }}人关注</div>
  </div>
</li>