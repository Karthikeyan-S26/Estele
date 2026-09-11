@props(['blog'])

<a class="block" href="{{ route('blogs.show', $blog) }}">
  <span class="block overflow-hidden bg-placeholder rounded-lg" style="aspect-ratio: 4/3;">
    @if($blog->hasMedia('featured_image'))
      <img class="h-full w-full object-cover transition-transform duration-700 hover:scale-105"
           src="{{ $blog->getFirstMediaUrl('featured_image', 'card') }}"
           alt="{{ $blog->featured_image_alt_text ?: $blog->title }}" loading="lazy">
    @endif
  </span>
  <p class="mt-3 text-[11px] uppercase tracking-[0.3px] text-muted">
    {{ $blog->blogCategory?->name }}
    @if($blog->published_at)
      · {{ $blog->published_at->format('M j, Y') }}
    @endif
  </p>
  <h3 class="mt-1 text-[15px] font-medium text-heading">{{ $blog->title }}</h3>
  @if($blog->excerpt)
    <p class="mt-1 text-[13px] text-muted line-clamp-2">{{ $blog->excerpt }}</p>
  @endif
</a>
