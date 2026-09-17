<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('meta_title', 'Vendor Portal | Estele')</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600&family=Playfair+Display:wght@500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap">
  <link rel="stylesheet" href="{{ asset('theme/app.css') }}?v={{ @filemtime(public_path('theme/app.css')) }}">
</head>
<body class="min-h-screen bg-ivory text-heading">

  <header class="border-b border-line bg-white">
    <div class="mx-auto flex w-full max-w-3xl items-center justify-between px-4 py-4 sm:px-6">
      <div class="flex items-baseline gap-2">
        <span class="font-display text-[16px] uppercase tracking-[2px] text-heading">Estele</span>
        <span class="rounded-full bg-pinksoft px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.4px] text-accent-dark">Vendor</span>
      </div>
      <form action="{{ route('vendor.logout') }}" method="post">
        @csrf
        <button class="inline-flex items-center gap-1.5 text-[12px] font-medium text-muted transition-colors hover:text-accent" type="submit">
          Sign out
          <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
        </button>
      </form>
    </div>

    @if (($vendor ?? null))
      <div class="border-t border-line bg-pinksoft/40">
        <div class="mx-auto flex w-full max-w-3xl items-center gap-2 px-4 py-2 text-[12.5px] text-heading sm:px-6">
          <svg class="h-3.5 w-3.5 shrink-0 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
          <span class="truncate">{{ $vendor->name }}{{ $vendor->company_name ? ' · '.$vendor->company_name : '' }}</span>
        </div>
      </div>
    @endif

    <nav class="mx-auto flex w-full max-w-3xl gap-2 overflow-x-auto px-4 pb-3 pt-3 sm:px-6" aria-label="Vendor navigation">
      <a href="{{ route('vendor.dashboard') }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-4 py-2 text-[12px] font-medium uppercase tracking-[0.3px] transition-colors {{ request()->routeIs('vendor.dashboard') || request()->routeIs('vendor.requests.*') ? 'border-accent bg-accent text-white' : 'border-line text-heading hover:border-accent hover:text-accent' }}">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
        Dashboard
      </a>
      <a href="{{ route('vendor.bids') }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-4 py-2 text-[12px] font-medium uppercase tracking-[0.3px] transition-colors {{ request()->routeIs('vendor.bids') ? 'border-accent bg-accent text-white' : 'border-line text-heading hover:border-accent hover:text-accent' }}">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Bids &amp; Settlements
      </a>
      <a href="{{ route('vendor.profile') }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-4 py-2 text-[12px] font-medium uppercase tracking-[0.3px] transition-colors {{ request()->routeIs('vendor.profile') ? 'border-accent bg-accent text-white' : 'border-line text-heading hover:border-accent hover:text-accent' }}">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
        Profile
      </a>
    </nav>
  </header>

  <main class="mx-auto w-full max-w-3xl px-4 py-6 sm:px-6 sm:py-8">

    @if (session('success'))
      <div class="mb-5 rounded-lg border border-line bg-pinksoft px-4 py-3 text-[13px] text-heading">{{ session('success') }}</div>
    @endif
    @if (session('error'))
      <div class="mb-5 rounded-lg border border-salebadge/30 bg-red-50 px-4 py-3 text-[13px] text-salebadge">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
      <div class="mb-5 rounded-lg border border-salebadge/30 bg-red-50 px-4 py-3 text-[13px] text-salebadge">
        <ul class="list-disc space-y-1 pl-4">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @yield('content')

  </main>

  <footer class="mt-10 border-t border-line py-6 text-center text-[11px] text-muted">
    &copy; {{ date('Y') }} Estele Accessories Pvt. Ltd. &middot; Vendor Portal
  </footer>

</body>
</html>
