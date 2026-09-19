{{--
  Fixed mobile tab bar. Hidden from md up, where the header's own nav already
  carries these destinations.

  Each item marks itself current from the route name rather than the URL, so a
  nested page (a category, an order, the sell-jewellery flow) still lights up
  its parent tab. routeIs patterns are deliberately broad for that reason.

  The bar sits above the safe-area inset so the labels clear the home indicator
  on notched phones; body gets matching bottom padding in the layout, and the
  floating chat button is already offset to bottom-[74px] to sit above it.
--}}
@php($tabs = [
  [
    'label' => 'Home',
    'url' => route('home'),
    'active' => request()->routeIs('home'),
    'icon' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V9.5"/>',
  ],
  [
    'label' => 'Categories',
    'url' => route('categories.index'),
    'active' => request()->routeIs('categories.*'),
    'icon' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
  ],
  [
    'label' => 'Wishlist',
    'url' => route('wishlist'),
    'active' => request()->routeIs('wishlist'),
    'icon' => '<path d="M12 20.5s-7.5-4.6-7.5-9.6a4.2 4.2 0 0 1 7.5-2.6 4.2 4.2 0 0 1 7.5 2.6c0 5-7.5 9.6-7.5 9.6z"/>',
  ],
  [
    'label' => 'Account',
    'url' => auth()->check() ? route('account.index') : route('login'),
    'active' => request()->routeIs('account.*') || request()->routeIs('login*'),
    'icon' => '<circle cx="12" cy="8" r="3.5"/><path d="M4.5 20c0-3.6 3.4-6 7.5-6s7.5 2.4 7.5 6"/>',
  ],
])

<nav class="bottom-nav" aria-label="Primary">
  @foreach($tabs as $tab)
    <a class="bottom-nav-item @if($tab['active']) is-active @endif"
       href="{{ $tab['url'] }}"
       @if($tab['active']) aria-current="page" @endif>
      <span class="bottom-nav-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $tab['icon'] !!}</svg>
        @if($tab['label'] === 'Wishlist')
          <span class="bottom-nav-badge" data-wishlist-count hidden>0</span>
        @endif
      </span>
      <span class="bottom-nav-label">{{ $tab['label'] }}</span>
    </a>
  @endforeach
</nav>
