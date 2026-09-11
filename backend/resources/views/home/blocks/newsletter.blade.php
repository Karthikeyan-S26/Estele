@props(['block'])

<section class="bg-pinksoft py-10 text-center md:py-[60px]">
  <div class="mx-auto w-full max-w-wrapper px-3 md:px-4">
    <h2 class="mb-2 text-[19px] md:text-[28px]">{{ $block->title }}</h2>
    @if($block->subtitle)
      <p class="mb-[22px] text-[13px] uppercase tracking-[0.5px] text-muted">{{ $block->subtitle }}</p>
    @endif
    <form class="mx-auto flex max-w-[520px] flex-col gap-2.5 sm:flex-row" data-newsletter>
      <label class="sr-only-custom" for="nl-email">Email address</label>
      <input class="w-full border border-line-strong bg-white px-4 py-3 text-[14px] outline-none transition-colors placeholder:text-muted focus:border-heading flex-1" id="nl-email" type="email" name="email" placeholder="Enter your email" required>
      <button class="inline-flex items-center justify-center gap-2 border border-accent bg-accent px-8 py-[13px] text-[13px] font-medium uppercase tracking-[0.5px] text-white transition-colors hover:border-accent-dark hover:bg-accent-dark" type="submit">Subscribe</button>
    </form>
    <p class="mt-3 text-[13px] text-[#428445]" data-newsletter-msg hidden>Thanks for subscribing.</p>
  </div>
</section>
