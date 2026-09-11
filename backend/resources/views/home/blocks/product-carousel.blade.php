@props(['block'])

@php $products = $block->items->pluck('itemable')->filter(); @endphp

@if($products->isNotEmpty())
  <section class="py-6 md:py-9">
    <div class="mx-auto w-full max-w-wrapper px-3 md:px-4">
      <x-section-header align="left" :eyebrow="$block->subtitle ?: 'Handpicked for you'" :title="$block->title ?: 'Bestsellers'" :cta-label="$block->cta_label ?: 'View all'" :cta-url="$block->cta_url ?: route('categories.index')" />
      <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 md:grid-cols-4 md:gap-4 lg:grid-cols-5 xl:gap-5">
        @foreach($products->take(10) as $product)
          <x-product-card :product="$product" />
        @endforeach
      </div>
    </div>
  </section>
@endif
