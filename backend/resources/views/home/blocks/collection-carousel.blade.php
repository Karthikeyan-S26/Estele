@props(['block'])

@php $collections = $block->items->pluck('itemable')->filter(); @endphp

@if($collections->isNotEmpty())
  <section class="bg-warmbeige py-6 md:py-9">
    <div class="mx-auto w-full max-w-wrapper px-3 md:px-4">
      <x-section-header eyebrow="Signature Edits" :title="$block->title" :subtitle="$block->subtitle" :cta-label="$block->cta_label" :cta-url="$block->cta_url" />
      <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 sm:gap-4 md:grid-cols-4 md:gap-5 lg:grid-cols-5 lg:gap-6">
        @foreach($collections as $collection)
          <a class="cat-tile block" href="{{ route('collections.show', $collection) }}">
            {{-- Collection artwork arrives in two very different shapes (wide
                 2.4:1 banners and 2:3 portraits), so object-contain letterboxed
                 them to visibly different sizes inside identical boxes. A 4:5
                 box plus object-cover renders every tile at one size; the
                 centre of both shapes survives the crop. --}}
            <span class="relative block aspect-[4/5] overflow-hidden rounded-[6px] border border-line bg-paper">
              @if($collection->hasMedia('image'))
                <img class="cat-tile__img"
                     src="{{ $collection->getFirstMediaUrl('image', 'tile') }}"
                     alt="{{ $collection->name }}" loading="lazy">
              @endif
            </span>
            <p class="mt-2.5 text-center text-[10.5px] font-medium uppercase leading-tight tracking-[0.06em] text-heading md:mt-3 md:text-[12px] md:tracking-[0.1em] lg:mt-3.5 lg:text-[13px] xl:text-[13.5px]">{{ $collection->name }}</p>
          </a>
        @endforeach
      </div>
    </div>
  </section>
@endif
