@extends('layouts.app')

@section('meta_title', 'My Addresses | '.($siteSettings['site_name'] ?? 'Estele'))

@section('content')

  <nav class="mx-auto w-full max-w-wrapper px-3 md:px-4 flex flex-wrap items-center gap-1.5 py-4 text-[13px] text-muted" aria-label="Breadcrumb">
    <x-breadcrumb :items="[['label' => 'My Account', 'url' => route('account.index')], ['label' => 'Addresses']]" />
  </nav>

  <div class="mx-auto w-full max-w-wrapper px-3 pb-10 md:px-4 md:pb-[60px]">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
      <h1 class="text-[20px] uppercase tracking-[0.5px] md:text-[26px]">My Addresses</h1>
      <a class="text-[13px] font-medium text-heading underline hover:text-accent" href="{{ route('account.index') }}">&larr; Back to Account</a>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2">
      @foreach($addresses as $address)
        <div class="rounded-lg border border-line p-4">
          <div class="mb-2 flex items-center justify-between gap-2">
            <span class="text-[13px] font-medium uppercase tracking-[0.3px] text-heading">{{ $address->label }}</span>
            @if($address->is_default)
              <span class="rounded-full bg-pinksoft px-2.5 py-0.5 text-[10px] font-medium uppercase tracking-[0.3px] text-accent">Default</span>
            @endif
          </div>
          <p class="text-[13px] text-muted">
            {{ $address->line1 }}{{ $address->line2 ? ', '.$address->line2 : '' }},
            {{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}, {{ $address->country }}
            @if($address->phone) <br>Phone: {{ $address->phone }} @endif
          </p>

          <div class="mt-3 flex items-center gap-4">
            <details class="[&_summary]:cursor-pointer">
              <summary class="text-[12px] font-medium uppercase tracking-[0.3px] text-heading underline hover:text-accent">Edit</summary>
              <form class="mt-3 border-t border-line pt-3" action="{{ route('account.addresses.update', $address) }}" method="post">
                @csrf
                @method('PATCH')
                @include('account._address-fields', ['address' => $address])
                <button class="mt-3 inline-flex items-center justify-center gap-2 border border-accent bg-accent px-6 py-2.5 text-[12px] font-medium uppercase tracking-[0.5px] text-white transition-colors hover:border-accent-dark hover:bg-accent-dark" type="submit">
                  Save Changes
                </button>
              </form>
            </details>

            <form action="{{ route('account.addresses.destroy', $address) }}" method="post" onsubmit="return confirm('Remove this address?');">
              @csrf
              @method('DELETE')
              <button class="text-[12px] font-medium uppercase tracking-[0.3px] text-salebadge underline hover:opacity-80" type="submit">Remove</button>
            </form>
          </div>
        </div>
      @endforeach
    </div>

    <details class="rounded-lg border border-line p-4">
      <summary class="cursor-pointer text-[13px] font-medium uppercase tracking-[0.4px] text-heading">+ Add New Address</summary>
      <form class="mt-4" action="{{ route('account.addresses.store') }}" method="post">
        @csrf
        @include('account._address-fields')
        <button class="mt-3 inline-flex items-center justify-center gap-2 border border-accent bg-accent px-6 py-2.5 text-[12px] font-medium uppercase tracking-[0.5px] text-white transition-colors hover:border-accent-dark hover:bg-accent-dark" type="submit">
          Save Address
        </button>
      </form>
    </details>
  </div>

@endsection
