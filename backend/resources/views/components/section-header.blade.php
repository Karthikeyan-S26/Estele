@props(['title', 'subtitle' => null, 'eyebrow' => null, 'ctaLabel' => null, 'ctaUrl' => null, 'tone' => 'light', 'align' => 'center'])

@if($align === 'left')
  <div class="mb-4 flex items-end justify-between gap-4 md:mb-7">
    <div>
      @if($eyebrow)
        <p class="section-head__eyebrow">{{ $eyebrow }}</p>
      @endif
      <h2 class="section-head__title {{ $tone === 'dark' ? 'text-white' : '' }}">{{ $title }}</h2>
      @if($subtitle)
        <p class="section-head__sub mx-0 {{ $tone === 'dark' ? 'text-white/70' : '' }}">{{ $subtitle }}</p>
      @endif
    </div>
    @if($ctaLabel)
      <a class="section-head__cta {{ $tone === 'dark' ? 'text-white' : 'text-heading' }}" href="{{ $ctaUrl ?? '#' }}">{{ $ctaLabel }}<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
    @endif
  </div>
@else
  <div class="section-head mb-5 md:mb-9">
    @if($eyebrow)
      <p class="section-head__eyebrow">{{ $eyebrow }}</p>
    @endif
    <h2 class="section-head__title {{ $tone === 'dark' ? 'text-white' : '' }}">{{ $title }}</h2>
    <span class="section-head__rule"></span>
    @if($subtitle)
      <p class="section-head__sub {{ $tone === 'dark' ? 'text-white/70' : '' }}">{{ $subtitle }}</p>
    @endif
    @if($ctaLabel)
      <a class="mt-4 inline-flex items-center border-b border-gold pb-1 text-[12px] font-medium uppercase tracking-[0.14em] transition-colors hover:text-gold {{ $tone === 'dark' ? 'text-white' : 'text-heading' }}" href="{{ $ctaUrl ?? '#' }}">{{ $ctaLabel }}</a>
    @endif
  </div>
@endif
