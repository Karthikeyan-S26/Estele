import 'dart:async';

import 'package:flutter/material.dart';

import '../../../models/home_data.dart';
import '../../../theme/app_colors.dart';
import '../../../theme/app_typography.dart';
import '../../../utils/app_link.dart';
import '../../../widgets/app_image.dart';

/// Hero carousel with automatic rotation + progress dots. Uses the purpose-shot
/// banner images (the copy is baked into the artwork), so any banner title the
/// CMS provides stays as a small chip instead of overlapping the art.
class HeroCarousel extends StatefulWidget {
  const HeroCarousel({super.key, required this.banners});

  final List<HomeBanner> banners;

  @override
  State<HeroCarousel> createState() => _HeroCarouselState();
}

class _HeroCarouselState extends State<HeroCarousel> {
  final _controller = PageController();
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    if (widget.banners.length > 1) {
      _timer = Timer.periodic(const Duration(seconds: 4), (_) {
        if (!_controller.hasClients) return;
        final next = (_controller.page ?? 0).round() + 1;
        _controller.animateToPage(
          next % widget.banners.length,
          duration: const Duration(milliseconds: 500),
          curve: Curves.easeInOut,
        );
      });
    }
  }

  @override
  void dispose() {
    _timer?.cancel();
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 190,
      child: Stack(
        children: [
          PageView.builder(
            controller: _controller,
            itemCount: widget.banners.length,
            itemBuilder: (context, i) => _Slide(banner: widget.banners[i]),
          ),
          if (widget.banners.length > 1)
            Positioned(
              bottom: 8,
              left: 0,
              right: 0,
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: List.generate(widget.banners.length, (i) {
                  return AnimatedBuilder(
                    animation: _controller,
                    builder: (_, __) {
                      final selected = (_controller.page ?? 0).round() == i;
                      return Container(
                        width: selected ? 16 : 6,
                        height: 6,
                        margin: const EdgeInsets.symmetric(horizontal: 3),
                        decoration: BoxDecoration(
                          color: selected ? AppColors.accent : AppColors.lineStrong,
                          borderRadius: BorderRadius.circular(3),
                        ),
                      );
                    },
                  );
                }),
              ),
            ),
        ],
      ),
    );
  }
}

class _Slide extends StatelessWidget {
  const _Slide({required this.banner});

  final HomeBanner banner;

  @override
  Widget build(BuildContext context) {
    final image = banner.mobileImageUrl ?? banner.imageUrl;
    final title = banner.title;

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 14),
      child: GestureDetector(
        onTap: () => resolveAppLink(context, banner.linkUrl),
        child: Stack(
          fit: StackFit.expand,
          children: [
            AppImage(url: image, borderRadius: BorderRadius.circular(4)),
            if (title != null && title.isNotEmpty)
              Align(
                alignment: Alignment.bottomLeft,
                child: Padding(
                  padding: const EdgeInsets.all(12),
                  child: Text(
                    title,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: AppTypography.label(size: 12, color: Colors.white, letterSpacing: 1.4),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}