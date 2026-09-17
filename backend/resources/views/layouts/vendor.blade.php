<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('meta_title', 'Vendor Portal | Estele')</title>
  <link rel="stylesheet" href="{{ asset('theme/app.css') }}?v={{ @filemtime(public_path('theme/app.css')) }}">
</head>
<body class="min-h-screen bg-ivory text-heading">

  {{-- Mobile top bar — the sidebar below is desktop-only (lg:flex). Plain
       data-attribute + vanilla JS toggle (see the script at the bottom of
       this file), matching how layouts/app.blade.php's own drawer works —
       no Alpine/Livewire on the public side of the site to hook into. --}}
  <header class="flex items-center justify-between border-b border-line bg-deepwine px-4 py-3 lg:hidden">
    <span class="font-display text-[15px] uppercase tracking-[2px] text-ivory">Estele <span class="text-gold">Vendor</span></span>
    <button class="grid h-9 w-9 place-items-center rounded-full text-ivory" type="button" data-vendor-nav-open aria-label="Menu" aria-expanded="false">
      <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
    </button>
  </header>

  <div class="lg:flex lg:min-h-screen">

    <div class="fixed inset-0 z-30 hidden bg-black/45" data-vendor-nav-backdrop data-vendor-nav-close></div>

    {{-- Sidebar --}}
    <aside
      class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-deepwine px-5 py-6 transition-transform duration-300 lg:sticky lg:top-0 lg:z-auto lg:h-screen lg:w-64 lg:translate-x-0 lg:shrink-0"
      data-vendor-nav
    >
      <div class="mb-8 flex items-center justify-between">
        <span class="font-display text-[16px] uppercase tracking-[2px] text-ivory">Estele <span class="text-gold">Vendor</span></span>
        <button class="grid h-8 w-8 place-items-center rounded-full text-ivory lg:hidden" type="button" data-vendor-nav-close aria-label="Close menu">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
      </div>

      <p class="mb-1 truncate text-[13px] font-medium text-ivory">{{ auth()->user()?->name }}</p>
      @if(($vendor ?? null)?->company_name)
        <p class="mb-6 truncate text-[11px] text-ivory/60">{{ $vendor->company_name }}</p>
      @else
        <div class="mb-6"></div>
      @endif

      <nav class="flex-1 space-y-1">
        <a href="{{ route('vendor.dashboard') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-[13px] font-medium transition-colors {{ request()->routeIs('vendor.dashboard') ? 'bg-white/10 text-gold' : 'text-ivory/80 hover:bg-white/5 hover:text-ivory' }}">
          <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
          Dashboard
        </a>
        <a href="{{ route('vendor.bids') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-[13px] font-medium transition-colors {{ request()->routeIs('vendor.bids') ? 'bg-white/10 text-gold' : 'text-ivory/80 hover:bg-white/5 hover:text-ivory' }}">
          <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          Bids &amp; Settlements
        </a>
        <a href="{{ route('vendor.profile') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-[13px] font-medium transition-colors {{ request()->routeIs('vendor.profile') ? 'bg-white/10 text-gold' : 'text-ivory/80 hover:bg-white/5 hover:text-ivory' }}">
          <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
          Profile
        </a>
      </nav>

      <form action="{{ route('vendor.logout') }}" method="post" class="mt-6">
        @csrf
        <button class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2.5 text-[13px] font-medium text-ivory/70 transition-colors hover:bg-white/5 hover:text-ivory" type="submit">
          <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
          Sign out
        </button>
      </form>
    </aside>

    {{-- Content --}}
    <main class="flex-1 px-4 py-6 sm:px-6 lg:px-10 lg:py-10">
      <div class="mx-auto w-full max-w-4xl">

        @if (session('success'))
          <div class="mb-5 rounded-lg border border-line bg-pinksoft px-4 py-3 text-[13px] text-heading">{{ session('success') }}</div>
        @endif
        @if (session('error'))
          <div class="mb-5 rounded-lg border border-salebadge/30 bg-red-50 p-3 px-4 py-3 text-[13px] text-salebadge">{{ session('error') }}</div>
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

      </div>
    </main>

  </div>

  <script src="{{ asset('theme/app.js') }}?v={{ @filemtime(public_path('theme/app.js')) }}" defer></script>
  <script>
    (function () {
      var nav = document.querySelector('[data-vendor-nav]');
      var backdrop = document.querySelector('[data-vendor-nav-backdrop]');
      var openBtn = document.querySelector('[data-vendor-nav-open]');
      var closeEls = document.querySelectorAll('[data-vendor-nav-close]');

      function setOpen(open) {
        nav.classList.toggle('-translate-x-full', ! open);
        backdrop.classList.toggle('hidden', ! open);
        openBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
      }

      openBtn.addEventListener('click', function () { setOpen(true); });
      closeEls.forEach(function (el) { el.addEventListener('click', function () { setOpen(false); }); });
    })();
  </script>
</body>
</html>
