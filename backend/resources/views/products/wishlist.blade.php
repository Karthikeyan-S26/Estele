@extends('layouts.app')

@section('meta_title', 'Saved Items | '.($siteSettings['site_name'] ?? 'Estele'))
@section('meta_description', 'The pieces you have saved to come back to.')

@section('content')

<section class="bg-ivory py-10 md:py-14">
  <div class="mx-auto w-full max-w-wrapper px-3 md:px-4">
    <x-section-header title="Saved Items" subtitle="Pieces you kept aside. They stay here on this device until you remove them." />

    {{-- Both states start hidden and app.js reveals the right one once it has
         read the saved list, so neither flashes on load. --}}
    <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 sm:gap-4 md:grid-cols-4 md:gap-5 lg:gap-6 xl:grid-cols-5 xl:gap-7 2xl:grid-cols-6" data-wishlist-grid hidden>
      @foreach($products as $product)
        <x-product-card :product="$product" />
      @endforeach
    </div>

    <div class="mx-auto max-w-[46ch] py-16 text-center" data-wishlist-empty hidden>
      <p class="font-serif text-[19px] text-heading">Nothing saved yet</p>
      <p class="mt-2.5 text-[13.5px] leading-relaxed text-muted">Tap the heart on any piece to keep it here while you decide.</p>
      <a class="mt-6 inline-flex items-center justify-center bg-accent px-8 py-3.5 text-[12px] font-medium uppercase tracking-[0.14em] text-white transition-colors hover:bg-accent-dark" href="{{ route('home') }}">Browse the collection</a>
    </div>
  </div>
</section>

@endsection
