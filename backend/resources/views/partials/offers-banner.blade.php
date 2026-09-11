@if(($activeOffers ?? []) !== [])
  <div class="mb-4.5 rounded border border-dashed border-line-strong p-3.5">
    <p class="mb-2 text-[11.5px] font-medium uppercase tracking-[0.5px] text-heading">Available Offers</p>
    <ul class="space-y-1.5 text-[12px] text-muted">
      @foreach($activeOffers as $offer)
        <li class="flex gap-1.5"><span>&bull;</span><span>{{ $offer }}</span></li>
      @endforeach
    </ul>
  </div>
@endif
