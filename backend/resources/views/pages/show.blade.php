@extends('layouts.app')

@section('meta_title', ($page->seoMeta?->title ?? $page->title).' | '.($siteSettings['site_name'] ?? 'Estele'))
@section('meta_description', $page->seoMeta?->description ?: $page->title)
@if($page->seoMeta?->og_image)
  @section('og_image', $page->seoMeta->og_image)
@endif

@section('content')

  <div class="mx-auto w-full max-w-[760px] px-3 py-6 md:px-4">
    <x-breadcrumb :items="[['label' => $page->title]]" />

    <h1 class="mb-6 text-[22px] uppercase tracking-[0.4px] md:text-[28px]">{{ $page->title }}</h1>

    <div class="prose max-w-none text-[14px] leading-relaxed text-ink">
      {!! $page->content !!}
    </div>
  </div>

@endsection
