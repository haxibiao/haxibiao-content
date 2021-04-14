<div>
    @if($collection)
    <a href="/collection/{{ $collection->id }}" class="category-label">
      <img src="{{ $collection->logo }}" alt="{{ $collection->name }}">
      <span class="name">{{ $collection->name }}</span>
    </a>
    @endif
</div>

