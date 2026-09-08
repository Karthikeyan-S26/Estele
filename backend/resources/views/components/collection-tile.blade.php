@props(['category', 'route' => 'categories.show', 'objectPosition' => 'center'])

<a class="cat-tile block" href="{{ route($route, $category) }}">
  <span class="cat-tile__frame block">
    @if($category->hasMedia('image'))
      <img class="cat-tile__img"
           style="object-position: {{ $objectPosition }};"
           src="{{ $category->getFirstMediaUrl('image', 'tile') }}"
           alt="{{ $category->image_alt_text ?: $category->name }}" loading="lazy">
    @endif
  </span>
  <p class="mt-2.5 text-center font-serif text-[12px] font-semibold leading-tight text-heading md:mt-3 md:text-[13.5px] lg:text-[14.5px]">{{ $category->name }}</p>
</a>
