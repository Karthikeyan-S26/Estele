import 'package:flutter/material.dart';

import '../../../models/collection.dart';
import '../../../theme/app_colors.dart';
import '../../../theme/app_typography.dart';
import '../../../widgets/app_image.dart';
import '../../../widgets/section_header.dart';
import '../category_products_screen.dart';

/// "Signature Edits → Shop by Collection" — mirrors
/// `home/blocks/collection-carousel.blade.php`:
///  - `section bg-warmbeige py-6` (warmbeige #F3EDE7 band), content `px-3`;
///  - centered section-head (eyebrow → title → gold rule → subtitle);
///  - `grid grid-cols-2 gap-2.5` of collection tiles;
///  - 4:5 box, `rounded-[6px] border-line`, paper bg; artwork `object-cover`;
///  - centered 10.5px uppercase semibold label, tracking 0.06em;
///  - when the block has more than 8 collections the grid gets
///    `explore-grid-4row` (4 rows × 2 cols = 8 tiles on mobile) and a
///    mobile-only "Explore more"/"Show less" toggle.
class CollectionsGrid extends StatefulWidget {
  const CollectionsGrid({super.key, required this.collections});

  final List<Collection> collections;

  @override
  State<CollectionsGrid> createState() => _CollectionsGridState();
}

class _CollectionsGridState extends State<CollectionsGrid> {
  bool _expanded = false;

  @override
  Widget build(BuildContext context) {
    if (widget.collections.isEmpty) return const SizedBox.shrink();

    final hasMore = widget.collections.length > 8;
    final shown = (_expanded
            ? widget.collections.take(20)
            : widget.collections.take(hasMore ? 8 : 20))
        .toList();

    return Container(
      color: AppColors.warmBeige, // bg-warmbeige
      padding: const EdgeInsets.symmetric(vertical: 24), // py-6
      width: double.infinity,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // .section-head mb-5 = 20px below the header block.
          const Padding(
            padding: EdgeInsets.fromLTRB(12, 0, 12, 20),
            child: SectionHeader(
              centered: true,
              scriptWord: 'Signature Edits',
              title: 'Shop by Collection',
            ),
          ),
          GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            padding: const EdgeInsets.symmetric(horizontal: 12),
            itemCount: shown.length,
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 2, // grid-cols-2
              crossAxisSpacing: 10, // gap-2.5
              mainAxisSpacing: 10,
              // 4:5 tile + mt-2.5 (10px) + label line.
              childAspectRatio: 4 / 5 * 0.78,
            ),
            itemBuilder: (context, i) {
              final collection = shown[i];
              return InkWell(
                borderRadius: BorderRadius.circular(6),
                onTap: () => Navigator.of(context).push(
                  MaterialPageRoute(
                    builder: (_) => CategoryProductsScreen(
                      title: collection.name,
                      categorySlug: collection.slug,
                      isCollection: true,
                    ),
                  ),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    // aspect-[4/5] rounded-[6px] border border-line bg-paper
                    Expanded(
                      child: Container(
                        decoration: BoxDecoration(
                          color: AppColors.paper,
                          borderRadius: BorderRadius.circular(6),
                          border: Border.all(color: AppColors.line),
                        ),
                        clipBehavior: Clip.antiAlias,
                        child: AppImage(url: collection.imageUrl),
                      ),
                    ),
                    const SizedBox(height: 10), // mt-2.5
                    // 10.5px uppercase semibold, tracking 0.06em, centered
                    Text(
                      collection.name,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      textAlign: TextAlign.center,
                      style: AppTypography.body(
                        size: 10.5,
                        color: AppColors.heading,
                        weight: FontWeight.w600,
                      ).copyWith(letterSpacing: 10.5 * 0.06),
                    ),
                  ],
                ),
              );
            },
          ),
          if (hasMore)
            // mt-5 text-center, `sm:hidden` on the web — our app is mobile.
            Padding(
              padding: const EdgeInsets.only(top: 20),
              child: Center(
                child: GestureDetector(
                  behavior: HitTestBehavior.opaque,
                  onTap: () => setState(() => _expanded = !_expanded),
                  child: Container(
                    padding: const EdgeInsets.only(bottom: 4), // pb-1
                    decoration: const BoxDecoration(
                      // border-b border-gold
                      border: Border(bottom: BorderSide(color: AppColors.gold)),
                    ),
                    child: Text(
                      _expanded ? 'Show less' : 'Explore more',
                      style: AppTypography.button(
                        size: 12,
                        color: AppColors.heading,
                        letterSpacing: 12 * 0.14,
                      ),
                    ),
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }
}