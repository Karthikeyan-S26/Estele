@extends('layouts.app')

@section('meta_title', 'FAQ | '.($siteSettings['site_name'] ?? 'Estele'))

@section('content')

  @php
    $allFaqs = $faqCategories->flatMap->faqs;
  @endphp

  @if($allFaqs->isNotEmpty())
    <script type="application/ld+json">
      {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $allFaqs->map(fn ($faq) => [
          '@type' => 'Question',
          'name' => $faq->question,
          'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => $faq->answer,
          ],
        ])->values()->all(),
      ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
    </script>
  @endif

  <div class="mx-auto w-full max-w-[760px] px-3 py-6 md:px-4">
    <x-breadcrumb :items="[['label' => 'FAQ']]" />

    <div class="mb-6 text-center">
      <h1 class="text-[20px] uppercase tracking-[0.5px] md:text-[26px] mb-2.5">Frequently Asked Questions</h1>
    </div>

    @if($faqCategories->isEmpty())
      <p class="py-10 text-center text-[13px] text-muted">No FAQs published yet.</p>
    @else
      <div class="space-y-8">
        @foreach($faqCategories as $category)
          <div>
            <h2 class="mb-3 text-[15px] font-medium uppercase tracking-[0.3px] text-heading">{{ $category->name }}</h2>
            <div class="divide-y divide-line border-y border-line">
              @foreach($category->faqs as $faq)
                <details class="group py-3">
                  <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-[13px] font-medium text-heading">
                    {{ $faq->question }}
                    <svg class="h-3.5 w-3.5 shrink-0 transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                  </summary>
                  <p class="mt-2 text-[13px] leading-relaxed text-muted">{{ $faq->answer }}</p>
                </details>
              @endforeach
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div>

@endsection
