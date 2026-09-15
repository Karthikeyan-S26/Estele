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
///    wraps — full "Necklace Sets" / "Pendant Sets", no ellipsis.
class CategoryStrip extends StatelessWidget {
  const CategoryStrip({super.key, required this.categories});

  final List<Category> categories;

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
              final circleSize = tileWidth.clamp(0.0, 150.0);

              return SizedBox(
                height: circleSize + 10 + 30, // frame + mt-2.5 + 2-line label
                child: ListView.separated(
                  padding: const EdgeInsets.symmetric(horizontal: 12),
                  scrollDirection: Axis.horizontal,
                  itemCount: categories.length,
                  separatorBuilder: (_, __) => const SizedBox(width: 8),
                  itemBuilder: (context, i) {
                    final category = categories[i];
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
                            // 2px border-line frame, placeholder bg behind.
                            Container(
                              width: circleSize,
                              height: circleSize,
                              decoration: BoxDecoration(
                                color: AppColors.greySoft, // bg-placeholder
                                shape: BoxShape.circle,
                                border: Border.all(
                                  color: AppColors.line, // border-line
                                  width: 2,
                                ),
                              ),
                              clipBehavior: Clip.antiAlias,
                              child: AppImage(url: category.imageUrl),
                            ),
                            const SizedBox(height: 10), // mt-2.5
                            // font-serif = Playfair Display, 12px, semibold,
                            // leading-tight (1.25) → two wrapped lines fit.
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
              );
            },
          ),
        ],
      ),
    );
  }
}
