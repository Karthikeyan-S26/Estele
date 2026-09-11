@props(['items'])

<nav class="mb-4 flex flex-wrap items-center gap-1 text-[11px] uppercase tracking-[0.3px] text-muted" aria-label="Breadcrumb">
  <a class="hover:text-accent" href="{{ route('home') }}">Home</a>
  @foreach($items as $item)
    <span aria-hidden="true">/</span>
    @if(!empty($item['url']))
      <a class="hover:text-accent" href="{{ $item['url'] }}">{{ $item['label'] }}</a>
    @else
      <span class="text-accent" aria-current="page">{{ $item['label'] }}</span>
    @endif
  @endforeach
</nav>
