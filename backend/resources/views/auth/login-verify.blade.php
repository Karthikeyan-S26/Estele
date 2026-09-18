@extends('layouts.app')

@section('meta_title', 'Verify Code | '.($siteSettings['site_name'] ?? 'Estele'))

@section('content')

<div class="min-h-[calc(100vh-220px)] bg-bagsurface md:py-16">
  <div class="mx-auto w-full max-w-[450px] md:px-4">
    <div class="bg-white px-5 pb-8 pt-7 md:rounded-[20px] md:p-8 md:shadow-md">

      <h1 class="mb-1 text-[16px] font-bold text-heading">Enter verification code</h1>
      <p class="mb-6 text-[14px] leading-5 text-[#454545]">
        Sent to <strong class="text-heading">{{ $phone }}</strong>
        <a class="ml-1 font-bold text-accent-dark underline" href="{{ route('login') }}">Edit</a>
      </p>

      <form action="{{ route('login.verify.attempt') }}" method="post" data-loading-submit data-otp-form>
        @csrf
        <input type="hidden" name="code" data-otp-value>

        <div class="grid grid-cols-6 gap-2">
          @for($i = 0; $i < 6; $i++)
            <input class="otp-box" type="text" inputmode="numeric" pattern="[0-9]*" aria-label="Digit {{ $i + 1 }}" {{ $i === 0 ? 'autocomplete=one-time-code autofocus' : 'autocomplete=off' }} data-otp-box>
          @endfor
        </div>
        @error('code') <p class="mt-2 text-[12px] text-salebadge">{{ $message }}</p> @enderror

        <button class="btn-cta mt-5 text-[14px] disabled:border-[#999] disabled:bg-[#999] disabled:bg-none disabled:opacity-100" type="submit" data-otp-submit>Verify &amp; Continue</button>
      </form>

      <form class="mt-3" action="{{ route('login.resend') }}" method="post" data-loading-submit>
        @csrf
        <button class="inline-flex min-h-11 w-full items-center justify-center text-center text-[14px] font-bold text-accent-dark disabled:cursor-not-allowed disabled:font-normal disabled:text-muted" type="submit" data-resend-cooldown="30">Didn't receive code? Resend</button>
      </form>

      <p class="mt-2 flex items-center justify-center gap-1.5 text-[14px] text-[#777676]">
        <svg class="h-5 w-5 text-success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v5c0 4.5-3 8.3-7 10-4-1.7-7-5.5-7-10V6z"/><path d="M9 12l2 2 4-4"/></svg>
        Secure login with OTP
      </p>

    </div>
  </div>
</div>

@endsection
