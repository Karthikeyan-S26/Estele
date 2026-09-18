@props(['category', 'route' => 'categories.show', 'objectPosition' => 'center'])

<a class="cat-tile block" href="{{ route($route, $category) }}">
  <span class="cat-tile__frame skeleton">
    @if($category->hasMedia('image'))
      <img class="cat-tile__img"
           style="object-position: {{ $objectPosition }};"
           src="{{ $category->getFirstMediaUrl('image', 'tile') }}"
           alt="{{ $category->image_alt_text ?: $category->name }}" loading="lazy">
    @endif
    <span class="cat-tile__label">{{ $category->name }}</span>
  </span>
</a>
