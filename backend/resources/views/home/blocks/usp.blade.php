@props(['block'])

@php
  $icons = [
    'M12 2 3 7v6c0 5 4 8.5 9 9 5-.5 9-4 9-9V7l-9-5Zm-1 13-3-3 1.4-1.4L11 12.2l4.6-4.6L17 9l-6 6Z',
    'M4 12a8 8 0 1 0 2.3-5.7L4 8.5M4 4v4.5h4.5',
    'M3 7h11v9H3zM14 10h4l3 3v3h-7zM6.5 19a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm11 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z',
    'M12 3 9.5 8.5 3.5 9l4.5 4-1.3 6L12 16l5.3 3-1.3-6 4.5-4-6-.5z',
    'M6 10V8a6 6 0 1 1 12 0v2m-13 0h14v11H5z',
    'M12 21s-7-4.5-7-10a7 7 0 0 1 14 0c0 5.5-7 10-7 10Zm0-7a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z',
  ];
@endphp

<section class="border-y border-line bg-white py-6 md:py-9">
  <div class="mx-auto w-full max-w-wrapper px-3 md:px-4">
    @if($block->title || $block->subtitle)
      <x-section-header :eyebrow="$block->cta_label ?: 'Since 1989'" :title="$block->title ?: 'Why Indian Women Choose Estele'" :subtitle="$block->subtitle" />
    @endif
    <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 md:gap-4 lg:grid-cols-6">
      @foreach($block->items as $item)
        <div class="flex flex-col items-center rounded-xl border border-line bg-paper px-3 py-5 text-center transition-all hover:-translate-y-0.5 hover:border-gold hover:shadow-md md:px-4 md:py-6">
          @if($item->hasMedia('image'))
            <img class="mb-3 h-10 w-10" src="{{ $item->getFirstMediaUrl('image') }}" alt="" loading="lazy" width="40" height="40">
          @else
            <span class="mb-3 grid h-11 w-11 place-items-center rounded-full bg-pinksoft text-rose">
              <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"><path d="{{ $icons[$loop->index % count($icons)] }}"/></svg>
            </span>
          @endif
          <h3 class="font-serif text-[14px] font-semibold leading-snug text-heading md:text-[15px]">{{ $item->title }}</h3>
          @if($item->body)
            <p class="mt-1.5 text-[11.5px] leading-relaxed text-muted md:text-[12.5px]">{{ $item->body }}</p>
          @endif
        </div>
      @endforeach
    </div>
  </div>
</section>
