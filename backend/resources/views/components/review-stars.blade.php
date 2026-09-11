@props(['rating' => 0, 'count' => null, 'size' => 'text-[13px]'])

<div class="inline-flex items-center gap-1.5" role="img" aria-label="{{ number_format((float) $rating, 1) }} out of 5 stars">
  <span class="{{ $size }} tracking-wider">
    @for($i = 1; $i <= 5; $i++)
      <span class="{{ $i <= round($rating) ? 'text-star' : 'text-line-strong' }}">&#9733;</span>
    @endfor
  </span>
  @if($count !== null)
    <span class="text-[12px] text-muted">({{ $count }})</span>
  @endif
</div>
