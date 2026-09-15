import 'package:flutter/material.dart';

import '../../../models/home_data.dart';
import '../../../theme/app_colors.dart';
import '../../../widgets/app_image.dart';
import '../../../widgets/section_header.dart';

/// "Styled by You → #EsteleQueens" — mirrors `home/blocks/shop-the-look.blade.php`:
///  - `section bg-ivory py-7`, content `px-3`;
///  - centered section-head (eyebrow → title → gold rule);
///  - mobile `grid grid-cols-4 gap-1.5` of square tiles (`rounded-lg`), each
///    with a hover overlay (title + gold price) and a small white camera badge.
class InstagramBlock extends StatelessWidget {
  const InstagramBlock({super.key, required this.section});

  final InstagramSection section;

  @override
  Widget build(BuildContext context) {
    final items = section.items;
    if (items.isEmpty) return const SizedBox.shrink();

    return Container(
      color: AppColors.ivory, // bg-ivory
      padding: const EdgeInsets.symmetric(vertical: 28), // py-7
      width: double.infinity,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // .section-head mb-5
          Padding(
            padding: const EdgeInsets.fromLTRB(12, 0, 12, 20),
            child: SectionHeader(
              centered: true,
              scriptWord: '@estele.co',
              title: 'Styled by You #EsteleQueens',
            ),
          ),
          GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            padding: const EdgeInsets.symmetric(horizontal: 12),
            itemCount: items.length,
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 4, // grid-cols-4 mobile
              mainAxisSpacing: 6, // gap-1.5 = 6px
              crossAxisSpacing: 6,
            ),
            itemBuilder: (context, i) {
              final item = items[i];
              return InkWell(
                borderRadius: BorderRadius.circular(8), // rounded-lg
                onTap: item.slug.isEmpty
                    ? null
                    : () => Navigator.of(
                        context,
                      ).pushNamed('/product/${item.slug}'),
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(8),
                  child: Stack(
                    fit: StackFit.expand,
                    children: [
                      AppImage(
                        url: item.imageUrl,
                        placeholderColor: AppColors.greySoft, // bg-placeholder
                      ),
                      // Camera badge — absolute right-1.5 top-1.5, 20px white
                      // circle (web shows the title/price overlay only on
                      // hover, which mobile never has).
                      Positioned(
                        right: 6,
                        top: 6,
                        child: Container(
                          width: 20,
                          height: 20,
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.9),
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(
                            Icons.camera_alt_outlined,
                            size: 12,
                            color: AppColors.heading,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
        ],
      ),
    );
  }
}