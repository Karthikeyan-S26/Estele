@extends('layouts.app')

@section('meta_title', 'Search'.($query !== '' ? " — {$query}" : '').' | '.($siteSettings['site_name'] ?? 'Estele'))

@section('content')

  <nav class="mx-auto w-full max-w-wrapper px-3 md:px-4 flex flex-wrap items-center gap-1.5 py-4 text-[13px] text-muted" aria-label="Breadcrumb">
    <x-breadcrumb :items="[['label' => 'Search']]" />
  </nav>

  <div class="mx-auto w-full max-w-wrapper px-3 md:px-4 pb-10 md:pb-[60px]">
    <h1 class="text-[20px] uppercase tracking-[0.5px] md:text-[26px] mb-5">Search</h1>

    @if($query !== '')
      <div class="mb-5 flex flex-wrap items-center gap-3.5">
        <p class="text-[13px] text-muted md:mr-auto">{{ $products->total() }} {{ \Illuminate\Support\Str::plural('result', $products->total()) }} for &ldquo;{{ $query }}&rdquo;</p>
        <label class="ml-auto">
          <span class="sr-only-custom">Sort by</span>
          <select class="border border-line-strong bg-white px-3 py-2 text-[13px] outline-none transition-colors focus:border-heading" onchange="window.location.href=this.value">
            <option value="{{ request()->fullUrlWithQuery(['sort' => 'relevance']) }}" @selected($sort === 'relevance')>Relevance</option>
            <option value="{{ request()->fullUrlWithQuery(['sort' => 'price_asc']) }}" @selected($sort === 'price_asc')>Price: Low to High</option>
            <option value="{{ request()->fullUrlWithQuery(['sort' => 'price_desc']) }}" @selected($sort === 'price_desc')>Price: High to Low</option>
            <option value="{{ request()->fullUrlWithQuery(['sort' => 'newest']) }}" @selected($sort === 'newest')>Newest</option>
          </select>
        </label>
      </div>

      <x-filter-panel
        :action="route('search')"
        :q="$query"
        :sort="$sort"
        :min-price="$minPrice"
        :max-price="$maxPrice"
        :in-stock="$inStock"
        :categories="$categories"
        :selected-categories="$categorySlugs"
      />
    @endif

    @if($products->isEmpty())
      <p class="py-16 text-center text-[13px] text-muted">
        @if($query === '')
          Use the search bar at the top of the page to find products.
        @else
          No products matched &ldquo;{{ $query }}&rdquo;.
        @endif
      </p>
    @else
      <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-4 md:gap-5">
        @foreach($products as $product)
          <x-product-card :product="$product" />
        @endforeach
      </div>

      <div class="mt-8">
        {{ $products->links() }}
      </div>
    @endif
  </div>

@endsection
