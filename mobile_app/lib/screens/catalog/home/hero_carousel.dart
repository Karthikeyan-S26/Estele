import 'dart:async';

import 'package:flutter/material.dart';

import '../../../models/home_data.dart';
import '../../../utils/app_link.dart';
import '../../../widgets/app_image.dart';

/// Hero carousel reproduces `home/index.blade.php` (the `data-fade` banner)
/// MOBILE presentation: full-bleed (no mx inset, square corners — the
/// `md:mx-4 md:rounded-2xl` card treatment is desktop-only), `h-[50vh]`
/// viewport box, `mobile_image` (or desktop→mobile conversion) with
/// `object-cover` — the copy is baked into the artwork, so banner titles
/// never overlay the image;
///  - chevron arrows left/right (mobile `h-6 w-6` 24px white/85 circles at
///    left-3/right-3, 10px chevron — the 40px circles are desktop `md:`);
///  - dots over the slide's lower edge (`bottom-4`): active is a 24px white
///    pill, inactive 6px white/55, gap-2;
///  - autoplay `data-autoplay="5000"` with a 700 ms crossfade.
class HeroCarousel extends StatefulWidget {
  const HeroCarousel({super.key, required this.banners});

  final List<HomeBanner> banners;

  @override
  State<HeroCarousel> createState() => _HeroCarouselState();
}

class _HeroCarouselState extends State<HeroCarousel> {
  int _index = 0;
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    if (widget.banners.length > 1) {
      _timer = Timer.periodic(const Duration(milliseconds: 5000), (_) {
        if (mounted) {
          setState(() => _index = (_index + 1) % widget.banners.length);
        }
      });
    }
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  void _goTo(int i) {
    setState(() => _index = (i + widget.banners.length) % widget.banners.length);
  }

  @override
  Widget build(BuildContext context) {
    final banner = widget.banners[_index];
    final image = banner.mobileImageUrl ?? banner.imageUrl;

    // Mobile blade: full-bleed `h-[50vh]` box, no inset, no rounding.
    return SizedBox(
      height: MediaQuery.sizeOf(context).height * 0.5,
      width: double.infinity,
      child: Stack(
        fit: StackFit.expand,
        children: [
              // Fade between slides (hero-fade, duration-700).
              AnimatedSwitcher(
                duration: const Duration(milliseconds: 700),
                child: GestureDetector(
                  key: ValueKey(_index),
                  onTap: () => resolveAppLink(context, banner.linkUrl),
                  child: AppImage(
                    url: image,
                    fit: BoxFit.cover, // object-cover
                  ),
                ),
              ),
              // Left arrow — white/85 40px circle, left-3, 16px chevron
              // (h-4 w-4 stroke-width 2, like the Blade SVGs).
              Positioned(
                left: 12,
                top: 0,
                bottom: 0,
                child: Center(
                  child: _AccentedButton(
                    icon: Icons.chevron_left_rounded,
                    onTap: () => _goTo(_index - 1),
                  ),
                ),
              ),
              Positioned(
                right: 12,
                top: 0,
                bottom: 0,
                child: Center(
                  child: _AccentedButton(
                    icon: Icons.chevron_right_rounded,
                    onTap: () => _goTo(_index + 1),
                  ),
                ),
              ),
              // Dots — bottom-4, gap-2, active w-6 white / inactive w-1.5 white/55.
              Positioned(
                left: 0,
                right: 0,
                bottom: 16,
                child: IgnorePointer(
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: List.generate(widget.banners.length, (i) {
                      final active = i == _index;
                      return AnimatedContainer(
                        duration: const Duration(milliseconds: 300),
                        margin: const EdgeInsets.symmetric(horizontal: 4),
                        width: active ? 24 : 6,
                        height: 6,
                        decoration: BoxDecoration(
                          color: active
                              ? Colors.white
                              : Colors.white.withValues(alpha: 0.55),
                          borderRadius: BorderRadius.circular(999),
                        ),
                      );
                    }),
                  ),
                ),
              ),
            ],
      ),
    );
  }
}

/// Round white/85 arrow button — mobile `h-6 w-6 rounded-full bg-white/85`
/// (the 40px circle is desktop `md:`), chevron 10px (`h-2.5 w-2.5`).
class _AccentedButton extends StatelessWidget {
  const _AccentedButton({required this.icon, required this.onTap});

  final IconData icon;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white.withValues(alpha: 0.85),
      shape: const CircleBorder(),
      child: InkWell(
        customBorder: const CircleBorder(),
        onTap: onTap,
        child: SizedBox(
          width: 24,
          height: 24,
          child: Center(
            child: Icon(icon, size: 10, color: const Color(0xFF1F1D1D)),
          ),
        ),
      ),
    );
  }
}