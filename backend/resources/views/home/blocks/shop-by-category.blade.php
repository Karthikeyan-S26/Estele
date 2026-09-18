@props(['block', 'categories'])

@if($categories->isNotEmpty())
  <section class="overflow-hidden bg-white pb-4 md:bg-ivory md:py-9">
    <div class="mx-auto w-full max-w-wrapper px-3 md:px-4">
      <x-section-header eyebrow="Curated Selections" :title="$block->title ?: 'Shop by Category'" :subtitle="$block->subtitle" :cta-label="$block->cta_label" :cta-url="$block->cta_url" />
      <div class="relative" data-loop>
        <button class="loop-arrow left-0" type="button" data-loop-prev aria-label="Previous categories">
          <svg class="h-3 w-3 md:h-4 md:w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
        </button>
        <div class="loop-stage" data-loop-stage>
          <div class="loop-track" data-loop-track>
            @foreach($categories as $category)
              <a class="loop__item" href="{{ route('categories.show', $category) }}" draggable="false">
                <span class="loop__frame skeleton">
                  @if($category->hasMedia('image'))
                    <img class="loop__img" src="{{ $category->getFirstMediaUrl('image', 'tile') }}" alt="{{ $category->image_alt_text ?: $category->name }}" loading="lazy" draggable="false">
                  @endif
                </span>
                <span class="loop__label">{{ $category->name }}</span>
              </a>
            @endforeach
          </div>
        </div>
        <button class="loop-arrow right-0" type="button" data-loop-next aria-label="Next categories">
          <svg class="h-3 w-3 md:h-4 md:w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
        </button>
      </div>
    </div>
  </section>
@endif
