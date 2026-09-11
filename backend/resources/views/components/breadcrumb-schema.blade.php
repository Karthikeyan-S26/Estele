@props(['items'])

{{-- BreadcrumbList JSON-LD matching the visible <x-breadcrumb> trail (SEO checklist item — every page with a breadcrumb should carry this). --}}
<script type="application/ld+json">
  {!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => collect($items)->values()->prepend(['label' => 'Home', 'url' => route('home')])
      ->map(fn ($item, $i) => array_filter([
        '@type' => 'ListItem',
        'position' => $i + 1,
        'name' => $item['label'],
        'item' => $item['url'] ?? null,
      ]))->all(),
  ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
</script>
