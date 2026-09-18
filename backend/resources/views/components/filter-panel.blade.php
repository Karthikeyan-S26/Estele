{{--
  Shared filter panel for /search results and category pages (Phase 5/8).
  Layout modeled on estele.co's own "Filter" toggle (collapsed by default,
  expands to price/availability/category facets + an Apply button), adapted
  to the fields this schema actually tracks — no invented stone-type/color
  facets since Product/Category don't carry those.

  All filtering is a plain GET form (no JS required to work) so it degrades
  gracefully and matches every other filter/sort control already in this
  codebase (see categories/show.blade.php's sort <select>).
--}}
@props([
  'action',
  'q' => null,
  'sort' => null,
  'minPrice' => null,
  'maxPrice' => null,
  'inStock' => false,
  'categories' => null,
  'selectedCategories' => [],
])

@php
  // Counted so a collapsed panel still says how many filters are narrowing the
  // list — on mobile the panel is shut by default and the count is the only
  // clue that what you are looking at is a filtered subset.
  $activeFilters = collect([
    filled($minPrice) || filled($maxPrice),
    (bool) $inStock,
  ])->filter()->count() + count($selectedCategories);
@endphp

<details class="md:mb-5 md:rounded-lg md:border md:border-line md:bg-paper" data-filter-panel {{ $activeFilters > 0 ? 'open' : '' }}>
  <summary class="hidden cursor-pointer select-none items-center gap-2 px-4 py-3 text-[13px] font-medium uppercase tracking-[0.1em] text-heading md:flex">
    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
    Filter
    @if($activeFilters > 0)
      <span class="grid h-[18px] min-w-[18px] place-items-center rounded-full bg-accent px-1 text-[10px] font-semibold leading-none text-white">{{ $activeFilters }}</span>
    @endif
  </summary>
  <form class="grid grid-cols-1 gap-5 p-4 sm:grid-cols-2 md:grid-cols-3 md:border-t md:border-line" method="get" action="{{ $action }}">
    <div class="-mx-4 -mt-4 flex items-center justify-between border-b border-line px-5 py-4 md:hidden">
      <span class="text-[16px] font-bold text-heading">Filter</span>
      <button class="grid h-8 w-8 place-items-center text-heading" type="button" data-filter-close aria-label="Close">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M5 5l14 14M19 5L5 19"/></svg>
      </button>
    </div>
    @if($q !== null)
      <input type="hidden" name="q" value="{{ $q }}">
    @endif
    @if($sort !== null)
      <input type="hidden" name="sort" value="{{ $sort }}">
    @endif

    <div>
      <p class="mb-2 text-[12px] font-medium uppercase tracking-[0.4px] text-heading">Price</p>
      <div class="flex items-center gap-2">
        <label class="sr-only-custom" for="filter-min-price">Minimum price</label>
        <input class="w-full border border-line-strong bg-white px-3 py-2 text-[13px] outline-none transition-colors focus:border-heading" id="filter-min-price" type="number" name="min_price" value="{{ $minPrice }}" placeholder="Min" min="0">
        <span class="text-muted">&ndash;</span>
        <label class="sr-only-custom" for="filter-max-price">Maximum price</label>
        <input class="w-full border border-line-strong bg-white px-3 py-2 text-[13px] outline-none transition-colors focus:border-heading" id="filter-max-price" type="number" name="max_price" value="{{ $maxPrice }}" placeholder="Max" min="0">
      </div>
    </div>

    <div>
      <p class="mb-2 text-[12px] font-medium uppercase tracking-[0.4px] text-heading">Availability</p>
      <label class="flex items-center gap-2 text-[13px] text-heading">
        <input type="checkbox" name="in_stock" value="1" @checked($inStock)>
        In stock only
      </label>
    </div>

    @if($categories && $categories->isNotEmpty())
      <div>
        <p class="mb-2 text-[12px] font-medium uppercase tracking-[0.4px] text-heading">Category</p>
        <div class="flex max-h-[140px] flex-col gap-1.5 overflow-y-auto">
          @foreach($categories as $cat)
            <label class="flex items-center gap-2 text-[13px] text-heading">
              <input type="checkbox" name="category[]" value="{{ $cat->slug }}" @checked(in_array($cat->slug, $selectedCategories, true))>
              {{ $cat->name }}
            </label>
          @endforeach
        </div>
      </div>
    @endif

    <div class="flex items-center gap-4 sm:col-span-2 md:col-span-3">
      <button class="btn-cta flex-1 text-[14px] sm:w-auto sm:flex-none sm:px-8" type="submit">Apply filters</button>
      @if($activeFilters > 0)
        <a class="text-[12px] text-muted underline transition-colors hover:text-accent" href="{{ $action }}{{ $q !== null ? '?q='.urlencode($q) : '' }}">Clear all</a>
      @endif
    </div>
  </form>
</details>
