@extends('layouts.app')

@section('meta_title', ($collection->seoMeta?->title ?? $collection->name).' | '.($siteSettings['site_name'] ?? 'Estele'))
@section('meta_description', $collection->seoMeta?->description ?: ($collection->description ?: $collection->name))
@if($collection->seoMeta?->og_image || $collection->hasMedia('image'))
  @section('og_image', $collection->seoMeta?->og_image ?? $collection->getFirstMediaUrl('image', 'banner'))
@endif

@section('content')

  <x-breadcrumb-schema :items="[['label' => $collection->name]]" />

  <nav class="mx-auto w-full max-w-wrapper px-3 md:px-4 flex flex-wrap items-center gap-1.5 py-4 text-[13px] text-muted" aria-label="Breadcrumb">
    <x-breadcrumb :items="[['label' => $collection->name]]" />
  </nav>

  <div class="mx-auto w-full max-w-wrapper px-3 md:px-4">
    <header class="pb-6 pt-2 text-center md:pb-[30px]">
      <h1 class="text-[20px] uppercase tracking-[0.5px] md:text-[26px] mb-2.5">{{ $collection->name }}</h1>
      @if($collection->description)
        <p class="mx-auto max-w-[70ch] text-[13.5px] text-muted">{{ $collection->description }}</p>
      @endif
    </header>
  </div>

  <div class="mx-auto w-full max-w-wrapper px-3 md:px-4 pb-10 md:pb-[60px]">

    <x-filter-panel
      :action="route('collections.show', $collection)"
      :sort="$sort"
      :min-price="$minPrice"
      :max-price="$maxPrice"
      :in-stock="$inStock"
    />

    <div class="mb-5 flex flex-wrap items-center gap-3 border-b border-line pb-4">
      <p class="text-[12.5px] text-muted md:text-[13px]">
        @if($products->total() > 0)
          Showing {{ $products->firstItem() }}&ndash;{{ $products->lastItem() }} of {{ $products->total() }}
        @else
          No products
        @endif
      </p>
      <label class="ml-auto">
        <span class="sr-only-custom">Sort by</span>
        <select class="border border-line-strong bg-white px-2.5 py-2 text-[12.5px] outline-none transition-colors focus:border-heading md:px-3 md:text-[13px]" onchange="window.location.href=this.value">
          <option value="{{ request()->fullUrlWithQuery(['sort' => 'featured']) }}" @selected($sort === 'featured')>Featured</option>
          <option value="{{ request()->fullUrlWithQuery(['sort' => 'price_asc']) }}" @selected($sort === 'price_asc')>Price: Low to High</option>
          <option value="{{ request()->fullUrlWithQuery(['sort' => 'price_desc']) }}" @selected($sort === 'price_desc')>Price: High to Low</option>
          <option value="{{ request()->fullUrlWithQuery(['sort' => 'newest']) }}" @selected($sort === 'newest')>Newest</option>
        </select>
      </label>
    </div>

    @if($products->isEmpty())
      <p class="py-16 text-center text-[13px] text-muted">No products match these filters. Try widening the price range.</p>
    @else
      <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 sm:gap-4 md:grid-cols-4 md:gap-5 lg:gap-6 xl:grid-cols-5 xl:gap-7 2xl:grid-cols-6">
        @foreach($products as $product)
          <x-product-card :product="$product" />
        @endforeach
      </div>

      <x-pagination-links :paginator="$products" />
      <div class="mt-8">
        {{ $products->links() }}
      </div>
    @endif
  </div>

@endsection
