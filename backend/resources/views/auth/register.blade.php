@extends('layouts.app')

@section('meta_title', 'Create Account | '.($siteSettings['site_name'] ?? 'Estele'))
@section('meta_description', 'Create an account to track your orders and check out faster.')

@section('content')

<div class="bg-white md:bg-pinksoft md:py-16">
  <div class="mx-auto w-full max-w-[450px] md:px-4">
    <div class="overflow-hidden bg-white md:rounded-[20px] md:shadow-md">

      <div class="grad-brand px-5 pb-6 pt-8 text-center md:px-8">
        <h1 class="mb-1 text-[19px] font-bold text-white">Create your account</h1>
        <p class="text-[13.5px] leading-5 text-white/85">Join Estele for exclusive member offers &amp; faster checkout.</p>
      </div>

      <div class="px-5 pb-8 pt-6 md:px-8">
        @if($errors->any())
          <div class="mb-4 rounded-[4px] border border-red-200 bg-red-50 px-4 py-3 text-[13px] text-red-700">
            Please correct the highlighted fields below.
          </div>
        @endif

        <form action="{{ route('register.attempt') }}" method="POST" class="space-y-4" data-loading-submit>
          @csrf

          <div>
            <label class="mb-1.5 block text-[13px] font-bold text-heading" for="name">Full Name</label>
            <div class="field border-line-strong focus-within:border-accent-dark">
              <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="First and last name" autocomplete="name" required autofocus>
            </div>
            @error('name') <p class="mt-1 text-[12px] text-salebadge">{{ $message }}</p> @enderror
          </div>

          <div>
            <label class="mb-1.5 block text-[13px] font-bold text-heading" for="phone">Mobile Number</label>
            <div class="field border-line-strong bg-pinksoft/40">
              <span class="text-[16px] font-semibold text-accent-dark">+91</span>
              <span class="h-5 w-px bg-line-strong"></span>
              <input id="phone" name="phone" type="tel" value="{{ old('phone', $prefillPhone) }}" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" required readonly>
              <svg class="h-5 w-5 shrink-0 text-success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v5c0 4.5-3 8.3-7 10-4-1.7-7-5.5-7-10V6z"/><path d="M9 12l2 2 4-4"/></svg>
            </div>
            <p class="mt-1.5 text-[13px] text-[#777676]">Verified via OTP.</p>
            @error('phone') <p class="mt-1 text-[12px] text-salebadge">{{ $message }}</p> @enderror
          </div>

          <button type="submit" class="btn-cta mt-1 text-[14px]">Create Account</button>
        </form>

        <p class="mt-6 text-center text-[13px] text-muted">
          Already have an account?
          <a class="font-bold text-accent-dark underline" href="{{ route('login') }}">Log in</a>
        </p>
      </div>

    </div>
  </div>
</div>

@endsection
