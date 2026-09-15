import 'package:flutter/material.dart';

import '../../../models/category.dart';
import '../../../theme/app_colors.dart';
import '../../../theme/app_typography.dart';
import '../../../widgets/app_image.dart';
import '../../../widgets/section_header.dart';
import '../category_products_screen.dart';

/// "Curated Selections → Shop by Category": a row of circle-cropped category
/// tiles from the backend Category model.
///
/// Mirrors `home/blocks/shop-by-category.blade.php`:
///  - `bg-ivory py-6` section, `px-3` container;
///  - centered section-head (eyebrow → title → gold rule);
///  - carousel track with `gap-2` (8px) and `flex-[0_0_calc((100%-3*8px)/4)]`
///    tiles — exactly four circles per mobile viewport, each sized to
///    1/4 of the track width;
///  - `cat-tile__frame`: aspect-square circle, 2px `border-line` (#EAE4DE),
///    `bg-placeholder` behind, max-width 150px, centered;
///  - label: `font-serif` (Playfair Display) 12px semibold, centered,
///    wraps — full "Necklace Sets" / "Pendant Sets", no ellipsis;
///  - chevron controls on both edges (`data-carousel-prev`/`-next`) — 28px
///    circles that stay visible on mobile (`grid h-7 w-7 -left-1`), growing
///    to 40px from md (`md:h-10 md:w-10 md:-left-2`); they scroll the track
///    one tile and fade out at the ends (`disabled:opacity-0`).
class CategoryStrip extends StatefulWidget {
  const CategoryStrip({super.key, required this.categories});

  final List<Category> categories;

  @override
  State<CategoryStrip> createState() => _CategoryStripState();
}

class _CategoryStripState extends State<CategoryStrip> {
  final ScrollController _controller = ScrollController();
  double _tileWidth = 0;
  int _offsetTiles = 0;
  int _maxOffsetTiles = 0;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _scrollBy({required int delta}) {
    setState(() => _offsetTiles = (_offsetTiles + delta).clamp(0, _maxOffsetTiles));
    _controller.animateTo(
      _offsetTiles * (_tileWidth + 8),
      duration: const Duration(milliseconds: 300),
      curve: Curves.easeOut,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      color: AppColors.ivory, // bg-ivory
      padding: const EdgeInsets.symmetric(vertical: 24), // py-6
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // .section-head mb-5 = 20px below the header block.
          const Padding(
            padding: EdgeInsets.fromLTRB(12, 0, 12, 20),
            child: SectionHeader(
              centered: true,
              scriptWord: 'Curated Selections',
              title: 'Shop by Category',
            ),
          ),
          LayoutBuilder(
            builder: (context, constraints) {
              // Tile width from the web's own formula:
              //   flex-[0_0_calc((100%-3*8px)/4)]  with gap-2 (8px)
              final trackWidth = constraints.maxWidth - 24; // px-3 each side
              final gap = 8.0;
              final tileWidth = (trackWidth - 3 * gap) / 4;
              _tileWidth = tileWidth;
              final circleSize = tileWidth.clamp(0.0, 150.0);
              _maxOffsetTiles =
                  (widget.categories.length - 4).clamp(0, 99).toInt();

              final prevVisible = _offsetTiles > 0;
              final nextVisible = _offsetTiles < _maxOffsetTiles;

              return SizedBox(
                height: circleSize + 10 + 30, // frame + mt-2.5 + 2-line label
                child: Stack(
                  alignment: Alignment.centerLeft,
                  children: [
                    // Track inset so the arrows (28px, half overlapping the
                    // first/last tile at -left-1/-right-1) never cover tiles.
                    Positioned(
                      left: 0,
                      right: 0,
                      top: 0,
                      bottom: 0,
                      child: ListView.separated(
                        controller: _controller,
                        padding: const EdgeInsets.symmetric(horizontal: 12),
                        scrollDirection: Axis.horizontal,
                        itemCount: widget.categories.length,
                        separatorBuilder: (context, _) =>
                            const SizedBox(width: 8),
                        itemBuilder: (context, i) {
                          final category = widget.categories[i];
                          return SizedBox(
                            width: tileWidth,
                            child: InkWell(
                              borderRadius: BorderRadius.circular(8),
                              onTap: () => Navigator.of(context).push(
                                MaterialPageRoute(
                                  builder: (_) => CategoryProductsScreen(
                                    title: category.name,
                                    categorySlug: category.slug,
                                    isCollection: false,
                                  ),
                                ),
                              ),
                              child: Column(
                                children: [
                                  // cat-tile__frame — aspect-square circle,
                                  // 2px border-line frame, placeholder bg.
                                  Container(
                                    width: circleSize,
                                    height: circleSize,
                                    decoration: BoxDecoration(
                                      color: AppColors.greySoft,
                                      shape: BoxShape.circle,
                                      border: Border.all(
                                        color: AppColors.line,
                                        width: 2,
                                      ),
                                    ),
                                    clipBehavior: Clip.antiAlias,
                                    child: AppImage(url: category.imageUrl),
                                  ),
                                  const SizedBox(height: 10), // mt-2.5
                                  Text(
                                    category.name,
                                    maxLines: 2,
                                    textAlign: TextAlign.center,
                                    style: AppTypography.editorial(
                                      size: 12,
                                      color: AppColors.heading,
                                      weight: FontWeight.w600,
                                      height: 1.25,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          );
                        },
                      ),
                    ),
                    // prev — absolute -left-1 grid h-7 w-7 md:h-10 md:w-10,
                    // invisible when scrolled to the start (disabled:opacity-0).
                    Positioned(
                      left: -4,
                      child: IgnorePointer(
                        ignoring: !prevVisible,
                        child: _TrackArrow(
                          icon: Icons.chevron_left_rounded,
                          visible: prevVisible,
                          onTap: () => _scrollBy(delta: -1),
                        ),
                      ),
                    ),
                    Positioned(
                      right: -4,
                      child: IgnorePointer(
                        ignoring: !nextVisible,
                        child: _TrackArrow(
                          icon: Icons.chevron_right_rounded,
                          visible: nextVisible,
                          onTap: () => _scrollBy(delta: 1),
                        ),
                      ),
                    ),
                  ],
                ),
              );
            },
          ),
        ],
      ),
    );
  }
}

/// Chevron circle — `rounded-full border border-line bg-white text-heading
/// shadow-sm`, 28px (`h-7 w-7`) with a 12px icon; invisible when `!visible`
/// (mirrors `disabled:opacity-0`).
class _TrackArrow extends StatelessWidget {
  const _TrackArrow({
    required this.icon,
    required this.visible,
    required this.onTap,
  });

  final IconData icon;
  final bool visible;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return AnimatedOpacity(
      opacity: visible ? 1 : 0,
      duration: const Duration(milliseconds: 200),
      child: Material(
        color: AppColors.paper, // bg-white
        shape: const CircleBorder(
          side: BorderSide(color: AppColors.line), // border border-line
        ),
        elevation: 1, // shadow-sm
        child: InkWell(
          customBorder: const CircleBorder(),
          onTap: onTap,
          child: SizedBox(
            width: 28, // h-7
            height: 28,
            child: Icon(icon, size: 12, color: AppColors.heading),
          ),
        ),
      ),
    );
  }
}