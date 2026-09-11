@if($activePopup)
  <div class="fixed inset-0 z-[230]" data-popup data-popup-id="{{ $activePopup['id'] }}" data-popup-trigger="{{ $activePopup['trigger'] }}" data-popup-delay="{{ $activePopup['delay_seconds'] }}" hidden>
    <div class="absolute inset-0 bg-black/45" data-popup-close></div>
    <div class="absolute left-1/2 top-1/2 w-[min(420px,92vw)] -translate-x-1/2 -translate-y-1/2 rounded-lg bg-white text-center">
      <button class="absolute right-3 top-2 text-[26px] leading-none text-heading" type="button" data-popup-close aria-label="Close">&times;</button>

      @if($activePopup['image_url'])
        <img class="h-40 w-full rounded-t-lg object-cover" src="{{ $activePopup['image_url'] }}" alt="{{ $activePopup['image_alt'] }}">
      @endif

      <div class="p-6">
        <h2 class="mb-2 text-[18px] font-medium uppercase tracking-[0.5px] text-heading">{{ $activePopup['title'] }}</h2>
        @if($activePopup['body'])
          <p class="mb-4 text-[13px] text-muted">{{ $activePopup['body'] }}</p>
        @endif
        @if($activePopup['discount_code'])
          <p class="mb-4 inline-block border border-dashed border-line-strong px-3.5 py-2 text-[13px] font-medium tracking-[0.3px] text-heading">{{ $activePopup['discount_code'] }}</p>
        @endif

        @if($activePopup['show_email_field'])
          <form class="flex flex-col gap-2.5" data-popup-newsletter-form action="{{ route('newsletter.subscribe') }}" method="post">
            @csrf
            <input type="hidden" name="popup_id" value="{{ $activePopup['id'] }}">
            <label class="sr-only-custom" for="popup-email">Email address</label>
            <input class="w-full border border-line-strong bg-white px-4 py-3 text-[14px] outline-none transition-colors placeholder:text-muted focus:border-heading" id="popup-email" type="email" name="email" placeholder="Enter your email" required>
            <button class="inline-flex items-center justify-center gap-2 border border-accent bg-accent px-8 py-[13px] text-[13px] font-medium uppercase tracking-[0.5px] text-white transition-colors hover:border-accent-dark hover:bg-accent-dark" type="submit">{{ $activePopup['cta_label'] ?: 'Subscribe' }}</button>
          </form>
          <p class="mt-3 text-[13px] text-[#428445]" data-popup-newsletter-msg hidden>Thanks for subscribing.</p>
        @elseif($activePopup['cta_url'])
          <a class="inline-flex items-center justify-center gap-2 border border-accent bg-accent px-8 py-[13px] text-[13px] font-medium uppercase tracking-[0.5px] text-white transition-colors hover:border-accent-dark hover:bg-accent-dark" href="{{ $activePopup['cta_url'] }}">{{ $activePopup['cta_label'] ?: 'Shop Now' }}</a>
        @endif
      </div>
    </div>
  </div>

  <script>
    (function () {
      var root = document.querySelector('[data-popup]');
      if (!root) return;

      var popupId = root.getAttribute('data-popup-id');
      var dismissKey = 'popup_dismissed_' + popupId;
      if (sessionStorage.getItem(dismissKey)) return;

      function show() {
        if (sessionStorage.getItem(dismissKey)) return;
        root.hidden = false;
      }

      function dismiss() {
        root.hidden = true;
        sessionStorage.setItem(dismissKey, '1');
      }

      root.querySelectorAll('[data-popup-close]').forEach(function (el) {
        el.addEventListener('click', dismiss);
      });

      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !root.hidden) dismiss();
      });

      var trigger = root.getAttribute('data-popup-trigger');
      if (trigger === 'exit_intent') {
        function onMouseLeave(e) {
          if (e.clientY <= 0) {
            show();
            document.removeEventListener('mouseleave', onMouseLeave);
          }
        }
        document.addEventListener('mouseleave', onMouseLeave);
      } else {
        var delaySeconds = parseInt(root.getAttribute('data-popup-delay'), 10) || 4;
        setTimeout(show, delaySeconds * 1000);
      }

      var form = root.querySelector('[data-popup-newsletter-form]');
      if (form) {
        form.addEventListener('submit', function (e) {
          e.preventDefault();
          var csrf = document.querySelector('meta[name="csrf-token"]');
          fetch(form.getAttribute('action'), {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'X-CSRF-TOKEN': csrf ? csrf.getAttribute('content') : '',
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: new FormData(form),
          }).then(function () {
            var msg = root.querySelector('[data-popup-newsletter-msg]');
            if (msg) msg.hidden = false;
            form.hidden = true;
            sessionStorage.setItem(dismissKey, '1');
          });
        });
      }
    })();
  </script>
@endif
