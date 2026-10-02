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
    // Blade iterates the whole block (`@foreach($collections)` — no take()); the
    // mobile clip to 8 comes purely from `explore-grid-4row`, so the expanded
    // state shows every collection.
    final shown = _expanded
        ? widget.collections
        : widget.collections.take(hasMore ? 8 : widget.collections.length).toList();

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
          // The blade's tile is `aspect-[4/5]` image + `mt-2.5` (10px) + a
          // 10.5px `leading-tight` (1.25) uppercase label — i.e. the tile is
          // 1.25×tileWidth + 10 + ~13.1px tall. Derive childAspectRatio from
          // the real column width so the 4:5 image box and the label both
          // land exactly as on the web instead of the old fixed 0.624 ratio
          // (which stretched tiles ~35% taller than the web's).
          LayoutBuilder(
            builder: (context, constraints) {
              const gridPadding = 12.0; // px-3 each side
              const gap = 10.0; // gap-2.5
              final avail = constraints.maxWidth - gridPadding * 2;
              final tileWidth = (avail - gap) / 2; // grid-cols-2 mobile
              final labelLineHeight = 10.5 * 1.25; // leading-tight
              final tileHeight = tileWidth * 1.25 + 10 + labelLineHeight;
              return GridView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                padding: const EdgeInsets.symmetric(horizontal: 12),
                itemCount: shown.length,
                gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 2, // grid-cols-2
                  crossAxisSpacing: 10, // gap-2.5
                  mainAxisSpacing: 10,
                  childAspectRatio: tileWidth / tileHeight,
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
                        AspectRatio(
                          aspectRatio: 4 / 5,
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
                        // 10.5px font-medium uppercase leading-tight label
                        Text(
                          collection.name.toUpperCase(),
                          maxLines: 2,
                          textAlign: TextAlign.center,
                          style: AppTypography.body(
                            size: 10.5,
                            color: AppColors.heading,
                            weight: FontWeight.w500,
                            height: 1.25,
                          ).copyWith(letterSpacing: 10.5 * 0.06),
                        ),
                      ],
                    ),
                  );
                },
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
                        weight: FontWeight.w500,
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