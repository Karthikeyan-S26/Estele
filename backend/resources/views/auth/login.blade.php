@extends('layouts.app')

@section('meta_title', 'Login with Mobile Number | '.($siteSettings['site_name'] ?? 'Estele'))
@section('meta_description', 'Login or create an account using your mobile number and a one-time code.')

@section('content')

<div class="bg-white md:bg-pinksoft md:py-16">
  <div class="mx-auto w-full max-w-[450px] md:px-4">
    <div class="overflow-hidden bg-white md:rounded-[20px] md:shadow-md">

      <div class="grad-brand px-5 pb-6 pt-8 text-center md:px-8">
        <h1 class="mb-1 text-[19px] font-bold text-white">Login for faster checkout</h1>
        <p class="text-[13.5px] leading-5 text-white/85">We'll send a one-time code. No password needed.</p>
      </div>

      <div class="px-5 pb-8 pt-6 md:px-8">
        <form action="{{ route('login.send') }}" method="post" data-loading-submit data-phone-form>
          @csrf

          <label class="sr-only-custom" for="phone">Mobile Number</label>
          <div class="field border-line-strong focus-within:border-accent-dark">
            <span class="text-[16px] font-semibold text-accent-dark">+91</span>
            <span class="h-5 w-px bg-line-strong"></span>
            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" inputmode="numeric" pattern="[0-9]*" maxlength="10" autocomplete="tel-national" required autofocus data-phone-input>
          </div>
          @error('phone') <p class="mt-1.5 text-[12px] text-salebadge">{{ $message }}</p> @enderror
          <p class="mt-2.5 text-[14px] leading-5 text-[#777676]">OTP will be sent to this number.</p>

          <button class="btn-cta mt-5 text-[14px] disabled:border-[#999] disabled:bg-[#999] disabled:bg-none disabled:opacity-100" type="submit" data-phone-submit>Send OTP</button>
        </form>

        <p class="mt-4 flex items-center justify-center gap-1.5 rounded-full bg-pinksoft px-4 py-2 text-[13px] font-medium text-accent-dark">
          <svg class="h-4.5 w-4.5 text-success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v5c0 4.5-3 8.3-7 10-4-1.7-7-5.5-7-10V6z"/><path d="M9 12l2 2 4-4"/></svg>
          Secure login with OTP
        </p>

        <p class="mt-6 text-center text-[12.5px] leading-relaxed text-muted">
          New here? You'll be guided to finish creating an account right after your number is verified.
        </p>
      </div>

    </div>
  </div>
</div>

@endsection
