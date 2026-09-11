@extends('layouts.app')

@section('meta_title', 'Page Not Found | '.($siteSettings['site_name'] ?? 'Estele'))

@section('content')
  <div class="mx-auto w-full max-w-[520px] px-3 py-16 text-center md:px-4 md:py-24">
    <p class="mb-2 text-[13px] font-medium uppercase tracking-[0.5px] text-accent">404</p>
    <h1 class="mb-3 text-[22px] md:text-[28px]">Page Not Found</h1>
    <p class="mb-8 text-[14px] text-muted">The page you're looking for doesn't exist or may have moved.</p>
    <a class="inline-flex items-center justify-center gap-2 border border-accent bg-accent px-8 py-[13px] text-[13px] font-medium uppercase tracking-[0.5px] text-white transition-colors hover:border-accent-dark hover:bg-accent-dark" href="{{ route('home') }}">
      Back to Home
    </a>
  </div>
@endsection
