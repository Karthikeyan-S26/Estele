@props(['sort' => null, 'options' => ['featured' => 'Featured', 'price_asc' => 'Price: Low to High', 'price_desc' => 'Price: High to Low', 'newest' => 'Newest'], 'filtered' => false])

<div class="fixed inset-x-0 bottom-0 z-[115] grid grid-cols-2 border-t border-line bg-white pb-[env(safe-area-inset-bottom)] shadow-[0_-2px_9px_rgba(0,0,0,0.08)] md:hidden">
  <button class="relative flex h-[52px] items-center justify-center gap-2 border-r border-line text-[13px] font-bold uppercase tracking-[0.12em] text-heading" type="button" data-sheet-open="sort">
    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 4v16M7 20l-3-3M7 20l3-3M17 20V4M17 4l-3 3M17 4l3 3"/></svg>
    Sort By
    @if($sort && $sort !== array_key_first($options))
      <span class="h-1.5 w-1.5 -translate-y-1.5 rounded-full bg-accent-dark"></span>
    @endif
  </button>
  <button class="relative flex h-[52px] items-center justify-center gap-2 text-[13px] font-bold uppercase tracking-[0.12em] text-heading" type="button" data-filter-open>
    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M3 5h18l-7 8v6l-4 2v-8z"/></svg>
    Filter
    @if($filtered)
      <span class="h-1.5 w-1.5 -translate-y-1.5 rounded-full bg-accent-dark"></span>
    @endif
  </button>
</div>

<div data-sheet="sort" hidden>
  <div class="sheet-backdrop" data-sheet-close></div>
  <div class="sheet md:hidden" role="dialog" aria-label="Sort by">
    <div class="flex items-center justify-between border-b border-line px-5 py-4">
      <span class="text-[16px] font-bold text-heading">Sort By</span>
      <button class="grid h-8 w-8 place-items-center text-heading" type="button" data-sheet-close aria-label="Close">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M5 5l14 14M19 5L5 19"/></svg>
      </button>
    </div>
    <ul class="px-5 pb-5 pt-1">
      @foreach($options as $value => $label)
        <li>
          <a class="flex items-center justify-between border-b border-line py-3.5 text-[15px] {{ $sort === $value ? 'font-bold text-accent-dark' : 'text-heading' }}" href="{{ request()->fullUrlWithQuery(['sort' => $value, 'page' => null]) }}" data-page-loading>
            {{ $label }}
            <span class="grid h-5 w-5 place-items-center rounded-full border {{ $sort === $value ? 'border-accent-dark' : 'border-line-strong' }}">
              @if($sort === $value)<span class="h-2.5 w-2.5 rounded-full bg-accent-dark"></span>@endif
            </span>
          </a>
        </li>
      @endforeach
    </ul>
  </div>
</div>
