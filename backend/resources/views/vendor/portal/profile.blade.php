@extends('layouts.vendor')

@section('meta_title', 'Profile | Vendor Portal | Estele')

@section('content')

  <h1 class="mb-1 font-display text-[20px] uppercase tracking-[0.5px] text-heading">Profile</h1>
  <p class="mb-6 text-[13px] text-muted">Your name, company and mobile are set by Estele — contact them to change these.</p>

  <div class="mb-6 rounded-xl border border-line bg-white p-5">
    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <dt class="text-[11px] uppercase tracking-[0.3px] text-muted">Name</dt>
        <dd class="mt-0.5 text-[14px] text-heading">{{ $vendor->name }}</dd>
      </div>
      <div>
        <dt class="text-[11px] uppercase tracking-[0.3px] text-muted">Company</dt>
        <dd class="mt-0.5 text-[14px] text-heading">{{ $vendor->company_name ?: '—' }}</dd>
      </div>
      <div>
        <dt class="text-[11px] uppercase tracking-[0.3px] text-muted">Mobile</dt>
        <dd class="mt-0.5 text-[14px] text-heading">
          {{ $vendor->mobile }}
          @if ($vendor->mobile_verified_at)
            <span class="ml-1.5 rounded-full bg-pinksoft px-2 py-0.5 text-[10.5px] font-medium text-accent-dark">Verified</span>
          @endif
        </dd>
      </div>
      <div>
        <dt class="text-[11px] uppercase tracking-[0.3px] text-muted">Email (login)</dt>
        <dd class="mt-0.5 text-[14px] text-heading">{{ $vendor->email }}</dd>
      </div>
    </dl>
  </div>

  <div class="mb-6 rounded-xl border border-line bg-white p-5">
    <h2 class="mb-3 text-[13px] font-medium uppercase tracking-[0.3px] text-heading">WhatsApp number</h2>
    <p class="mb-3 text-[12.5px] text-muted">Bid invites and links go here. Leave blank to use your mobile number.</p>
    <form action="{{ route('vendor.profile.contact') }}" method="post" class="flex flex-col gap-3 sm:flex-row">
      @csrf
      <input class="h-12 flex-1 rounded-lg border border-line-strong px-4 text-[14px] outline-none focus:border-accent focus:ring-1 focus:ring-accent" type="tel" name="whatsapp_number" value="{{ old('whatsapp_number', $vendor->whatsapp_number) }}" placeholder="WhatsApp number">
      <button class="h-12 shrink-0 rounded-lg border border-accent bg-accent px-6 text-[12.5px] font-semibold uppercase tracking-[0.5px] text-white transition-colors hover:border-accent-dark hover:bg-accent-dark" type="submit">
        Save
      </button>
    </form>
  </div>

  <div class="rounded-xl border border-line bg-white p-5">
    <h2 class="mb-3 text-[13px] font-medium uppercase tracking-[0.3px] text-heading">Change password</h2>
    <form action="{{ route('vendor.profile.password') }}" method="post" class="space-y-3">
      @csrf
      <div>
        <label class="mb-1.5 block text-[13px] font-medium text-heading" for="current_password">Current password</label>
        <input class="h-12 w-full rounded-lg border border-line-strong px-4 text-[14px] outline-none focus:border-accent focus:ring-1 focus:ring-accent" id="current_password" name="current_password" type="password" required autocomplete="current-password">
      </div>
      <div>
        <label class="mb-1.5 block text-[13px] font-medium text-heading" for="password">New password</label>
        <input class="h-12 w-full rounded-lg border border-line-strong px-4 text-[14px] outline-none focus:border-accent focus:ring-1 focus:ring-accent" id="password" name="password" type="password" placeholder="At least 8 characters" required autocomplete="new-password">
      </div>
      <div>
        <label class="mb-1.5 block text-[13px] font-medium text-heading" for="password_confirmation">Confirm new password</label>
        <input class="h-12 w-full rounded-lg border border-line-strong px-4 text-[14px] outline-none focus:border-accent focus:ring-1 focus:ring-accent" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
      </div>
      <button class="h-12 w-full rounded-lg border border-line bg-heading text-[12.5px] font-semibold uppercase tracking-[0.5px] text-white transition-colors hover:bg-black sm:w-auto sm:px-8" type="submit">
        Update Password
      </button>
    </form>
  </div>

@endsection
