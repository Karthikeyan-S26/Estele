<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Vendor Sign In | Estele</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600&display=swap">
  <link rel="stylesheet" href="{{ asset('theme/app.css') }}?v={{ @filemtime(public_path('theme/app.css')) }}">
</head>
<body class="flex min-h-screen items-center justify-center bg-ivory px-4 py-10">

  <div class="w-full max-w-[400px]">

    <div class="mb-6 text-center">
      <div class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-full bg-pinksoft text-accent">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 3v4M8 3v4M2 11h20"/></svg>
      </div>
      <span class="font-display text-[20px] uppercase tracking-[2px] text-heading">Estele <span class="text-gold">Vendor</span></span>
      <p class="mt-1 text-[12.5px] text-muted">Sign in to see your jewellery requests, bids and settlements.</p>
    </div>

    <div class="rounded-2xl border border-line bg-white p-6 shadow-sm sm:p-8">

      @if (session('status'))
        <p class="mb-4 rounded-lg border border-line bg-pinksoft px-4 py-3 text-[13px] text-heading">{{ session('status') }}</p>
      @endif
      @if (session('error'))
        <p class="mb-4 rounded-lg border border-salebadge/30 bg-red-50 px-4 py-3 text-[13px] text-salebadge">{{ session('error') }}</p>
      @endif
      @if ($errors->any())
        <p class="mb-4 rounded-lg border border-salebadge/30 bg-red-50 px-4 py-3 text-[13px] text-salebadge">{{ $errors->first() }}</p>
      @endif

      <form action="{{ route('vendor.login.store') }}" method="post" class="space-y-4">
        @csrf
        <div>
          <label class="mb-1.5 block text-[13px] font-medium text-heading" for="email">Email</label>
          <input class="h-12 w-full rounded-lg border border-line-strong bg-white px-4 text-[14px] outline-none transition-colors placeholder:text-muted focus:border-accent focus:ring-1 focus:ring-accent" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        </div>
        <div>
          <label class="mb-1.5 block text-[13px] font-medium text-heading" for="password">Password</label>
          <input class="h-12 w-full rounded-lg border border-line-strong bg-white px-4 text-[14px] outline-none transition-colors placeholder:text-muted focus:border-accent focus:ring-1 focus:ring-accent" id="password" name="password" type="password" required autocomplete="current-password">
        </div>
        <button class="mt-2 h-12 w-full rounded-lg bg-accent text-[13px] font-semibold uppercase tracking-[0.6px] text-white shadow-sm transition-colors hover:bg-accent-dark" type="submit">
          Sign In
        </button>
      </form>

    </div>

    <p class="mt-6 text-center text-[12px] text-muted">
      New here, or lost your setup link? Contact the Estele team that added you.
    </p>

  </div>

</body>
</html>
