{{--
  rel=next/prev <link> tags for a paginated listing page (spec §4.1). Usage:
  <x-pagination-links :paginator="$products" /> anywhere in the page body —
  it pushes into the 'pagination_links' stack rendered in <head> regardless
  of where in the page this component is placed.
--}}
@props(['paginator'])

@push('pagination_links')
  @if($paginator->onFirstPage() === false)
    <link rel="prev" href="{{ $paginator->previousPageUrl() }}">
  @endif
  @if($paginator->hasMorePages())
    <link rel="next" href="{{ $paginator->nextPageUrl() }}">
  @endif
@endpush
