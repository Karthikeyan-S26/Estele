@extends('layouts.app')

@section('meta_title', 'Verify Code | '.($siteSettings['site_name'] ?? 'Estele'))

@section('content')

<div class="grad-soft min-h-[calc(100vh-220px)] md:bg-bagsurface md:bg-none md:py-16">
  <div class="mx-auto w-full max-w-[450px] md:px-4">
    <div class="px-5 pb-8 pt-7 md:rounded-[20px] md:bg-white md:p-8 md:shadow-md">

      {{-- Shield mark, repeated at the foot of the form as a plain line. Here
           it gives the screen a single focal point above the heading. --}}
      <span class="mb-4 grid h-14 w-14 place-items-center rounded-full bg-pinksoft text-accent-dark" aria-hidden="true">
        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
          <rect x="4.5" y="10" width="15" height="10" rx="2.5"/>
          <path d="M8 10V7.5a4 4 0 0 1 8 0V10"/>
          <circle cx="12" cy="15" r="1.4" fill="currentColor" stroke="none"/>
        </svg>
      </span>

      <h1 class="mb-1.5 text-[26px] font-bold leading-tight text-heading">Enter Verification Code</h1>
      <p class="mb-1 text-[14px] leading-5 text-[#454545]">We've sent a 6-digit code to your mobile number</p>
      <p class="mb-6 text-[14px] leading-5 text-[#454545]">
        Sent to <strong class="text-heading">{{ $phone }}</strong>
        <a class="ml-1.5 inline-flex items-center gap-1 font-bold text-accent-dark" href="{{ route('login') }}">
          <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
          Edit
        </a>
      </p>

      <form action="{{ route('login.verify.attempt') }}" method="post" data-loading-submit data-otp-form>
        @csrf
        <input type="hidden" name="code" data-otp-value>

        {{-- placeholder=" " is what .otp-box:not(:placeholder-shown) keys off
             to keep a filled box brand-bordered once focus moves on. --}}
        <div class="grid grid-cols-6 gap-2">
          @for($i = 0; $i < 6; $i++)
            <input class="otp-box" type="text" inputmode="numeric" pattern="[0-9]*" placeholder=" " aria-label="Digit {{ $i + 1 }}" {{ $i === 0 ? 'autocomplete=one-time-code autofocus' : 'autocomplete=off' }} data-otp-box>
          @endfor
        </div>
        @error('code') <p class="mt-2 text-[12px] text-salebadge">{{ $message }}</p> @enderror

        <button class="btn-cta mt-5 gap-2 rounded-full text-[15px] disabled:border-[#999] disabled:bg-[#999] disabled:bg-none disabled:opacity-100" type="submit" data-otp-submit>
          Verify &amp; Continue
          <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h13M13 6l6 6-6 6"/></svg>
        </button>
      </form>

      {{-- Resend sits in its own tinted card: it's the only other action here,
           and the cooldown copy needs somewhere to live that isn't the button
           label (the script rewrites the label while counting down). --}}
      <form class="mt-4 rounded-2xl bg-pinksoft px-4 py-3.5 text-center" action="{{ route('login.resend') }}" method="post" data-loading-submit>
        @csrf
        <div class="flex items-center justify-center gap-2">
          <svg class="h-[18px] w-[18px] shrink-0 text-accent-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
          <button class="min-h-[22px] text-[14px] font-bold text-accent-dark disabled:cursor-not-allowed disabled:font-normal disabled:text-muted" type="submit" data-resend-cooldown="30">Didn't receive code? Resend</button>
        </div>
      </form>

      <p class="mt-4 flex items-center justify-center gap-1.5 text-[14px] text-[#777676]">
        <svg class="h-5 w-5 text-success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v5c0 4.5-3 8.3-7 10-4-1.7-7-5.5-7-10V6z"/><path d="M9 12l2 2 4-4"/></svg>
        Secure login with OTP
      </p>

      {{-- Reassurance strip, mobile only — on desktop the card sits in a page
           that already carries the same guarantees in the footer. --}}
      <div class="trust-strip mt-6 md:hidden">
        <div class="trust-col">
          <span class="trust-col-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1.5" y="7" width="13" height="9" rx="1.5"/><path d="M14.5 10H18l3.5 3v3h-7z"/><circle cx="6" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/></svg></span>
          <span class="trust-col-title">100% Anti-Tarnish</span>
          <span class="trust-col-note">Plating that stays bright</span>
        </div>
        <div class="trust-col">
          <span class="trust-col-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12a8 8 0 1 1 2.5 5.8"/><path d="M4 8v4h4"/></svg></span>
          <span class="trust-col-title">7-Day Easy Return</span>
          <span class="trust-col-note">Hassle-free exchange</span>
        </div>
        <div class="trust-col">
          <span class="trust-col-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l7 3v5c0 4.5-3 8.3-7 10-4-1.7-7-5.5-7-10V6z"/><path d="M9 12l2 2 4-4"/></svg></span>
          <span class="trust-col-title">Trusted by 10,000+</span>
          <span class="trust-col-note">Quality you can count on</span>
        </div>
      </div>

    </div>
  </div>
</div>

@endsection
