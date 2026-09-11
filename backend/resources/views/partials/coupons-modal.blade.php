<div class="fixed inset-0 z-[220]" data-coupons-modal hidden>
  <div class="absolute inset-0 bg-black/45" data-coupons-modal-close></div>
  <div class="absolute left-1/2 top-1/2 w-[min(420px,92vw)] -translate-x-1/2 -translate-y-1/2 rounded-lg bg-white">
    <div class="flex items-center justify-between border-b border-line px-5 py-[18px]">
      <span class="text-[13px] font-medium uppercase tracking-[1px]">Available Coupons</span>
      <button class="text-[26px] leading-none text-heading" type="button" data-coupons-modal-close aria-label="Close">&times;</button>
    </div>
    <div class="max-h-[60vh] overflow-y-auto p-5">
      @if(($publicCoupons ?? []) === [])
        <p class="py-6 text-center text-[13px] text-muted">No coupons available right now.</p>
      @else
        <ul class="space-y-3">
          @foreach($publicCoupons as $coupon)
            <li class="flex items-center justify-between gap-3 rounded border border-dashed border-line-strong px-3.5 py-3">
              <div>
                <p class="text-[13.5px] font-medium tracking-[0.3px] text-heading">{{ $coupon['code'] }}</p>
                <p class="mt-0.5 text-[12px] text-muted">{{ $coupon['summary'] }}</p>
              </div>
              <button class="shrink-0 border border-accent bg-accent px-3.5 py-2 text-[11px] font-medium uppercase tracking-[0.5px] text-white transition-colors hover:border-accent-dark hover:bg-accent-dark disabled:opacity-50" type="button" data-coupons-modal-apply="{{ $coupon['code'] }}">Apply</button>
            </li>
          @endforeach
        </ul>
      @endif
    </div>
  </div>
</div>
