@extends('layouts.app')

@section('meta_title', 'Complete Payment | '.($siteSettings['site_name'] ?? 'Estele'))

@section('content')

  <div class="mx-auto w-full max-w-[640px] px-3 py-12 text-center md:px-4 md:py-16">
    <h1 class="mb-2 text-[22px] md:text-[28px]">Complete your payment</h1>
    <p class="mb-8 text-[14px] text-muted">Order Number: <strong>{{ $order->order_number }}</strong></p>

    <div id="payment-error" class="mb-6 hidden border border-salebadge/40 bg-salebadge/10 p-4 text-left text-[13px] text-salebadge"></div>

    @if (session('error'))
      <div class="mb-6 border border-salebadge/40 bg-salebadge/10 p-4 text-left text-[13px] text-salebadge">{{ session('error') }}</div>
    @endif

    <div class="mb-8 rounded-lg border border-line p-5 text-left">
      <div class="flex items-center justify-between border-t border-line pt-3 text-[15px] first:border-t-0 first:pt-0">
        <span class="font-medium text-heading">Amount payable</span>
        <span class="font-medium text-price">₹{{ number_format($order->total, 0) }}</span>
      </div>
    </div>

    <button id="pay-now-btn" type="button" class="inline-flex w-full items-center justify-center gap-2 border border-accent bg-accent px-8 py-[13px] text-[13px] font-medium uppercase tracking-[0.5px] text-white transition-colors hover:border-accent-dark hover:bg-accent-dark">
      Pay Now
    </button>

    <form id="razorpay-callback-form" action="{{ route('payment.callback', $order) }}" method="post" class="hidden">
      @csrf
      <input type="hidden" name="razorpay_order_id">
      <input type="hidden" name="razorpay_payment_id">
      <input type="hidden" name="razorpay_signature">
    </form>
  </div>

  @push('scripts')
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
      document.getElementById('pay-now-btn').addEventListener('click', function () {
        var rzp = new Razorpay({
          key: @json($razorpayKeyId),
          amount: @json($amountPaise),
          currency: 'INR',
          name: @json($siteSettings['site_name'] ?? 'Estele'),
          order_id: @json($order->razorpay_order_id),
          prefill: {
            name: @json($order->customer_name),
            email: @json($order->customer_email),
            contact: @json($order->customer_phone),
          },
          handler: function (response) {
            var form = document.getElementById('razorpay-callback-form');
            form.querySelector('[name="razorpay_order_id"]').value = response.razorpay_order_id;
            form.querySelector('[name="razorpay_payment_id"]').value = response.razorpay_payment_id;
            form.querySelector('[name="razorpay_signature"]').value = response.razorpay_signature;
            form.submit();
          },
        });

        rzp.on('payment.failed', function (response) {
          var errorBox = document.getElementById('payment-error');
          errorBox.textContent = 'Payment failed: ' + (response.error && response.error.description ? response.error.description : 'please try again.');
          errorBox.classList.remove('hidden');
        });

        rzp.open();
      });
    </script>
  @endpush

@endsection
