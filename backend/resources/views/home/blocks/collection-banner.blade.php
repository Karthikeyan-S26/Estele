@props(['block'])

@php $item = $block->items->first(); @endphp
@php $collection = $item?->itemable; @endphp

@if($collection)
  <section class="py-4 md:py-6">
    <div class="mx-auto w-full max-w-wrapper px-3 md:px-4">
      <a class="group grid overflow-hidden rounded-xl bg-deepwine text-white md:grid-cols-2 md:rounded-2xl" href="{{ route('collections.show', $collection) }}">
        <span class="order-2 flex flex-col justify-center px-5 py-6 md:order-1 md:px-10 md:py-12 lg:px-14">
          @if($block->subtitle)
            <span class="section-head__eyebrow !text-gold">&#10022; {{ $block->subtitle }}</span>
          @endif
          <span class="font-serif text-[26px] font-semibold leading-tight md:text-[36px] lg:text-[42px]">{{ $block->title ?: $collection->name }}</span>
          @if($item->body ?: $collection->description)
            <span class="mt-3 max-w-[44ch] text-[13px] leading-relaxed text-white/70 md:text-[14px]">{{ $item->body ?: $collection->description }}</span>
          @endif
          <span class="mt-5 inline-flex w-fit items-center gap-2 rounded-md bg-rose px-5 py-2.5 text-[11px] font-semibold uppercase tracking-[0.14em] text-white transition-colors group-hover:bg-gold group-hover:text-deepwine md:text-[12px]">{{ $block->cta_label ?: 'Explore the collection' }}<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
        </span>
        <span class="relative order-1 block aspect-[16/9] overflow-hidden md:order-2 md:aspect-auto md:min-h-[320px]">
          @if($item->hasMedia('image'))
            <img class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-105" src="{{ $item->getFirstMediaUrl('image', 'banner') }}" alt="{{ $block->title }}" loading="lazy" width="1400" height="560">
          @elseif($collection->hasMedia('image'))
            <img class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-105" src="{{ $collection->getFirstMediaUrl('image', 'banner') }}" alt="{{ $collection->name }}" loading="lazy" width="1400" height="560">
          @endif
          <span class="absolute inset-y-0 left-0 hidden w-24 bg-gradient-to-r from-deepwine to-transparent md:block"></span>
        </span>
      </a>
    </div>
  </section>
@endif
