import 'package:flutter/material.dart';

import '../../../models/home_data.dart';
import '../../../models/product.dart';
import '../../../theme/app_colors.dart';
import '../../../theme/app_typography.dart';
import '../../../widgets/app_image.dart';
import '../../../widgets/section_header.dart';

/// "Styled by You → #EsteleQueens": square image tiles of customer-tagged
/// pieces, tappable into the product pages.
class InstagramBlock extends StatelessWidget {
  const InstagramBlock({super.key, required this.section});

  final InstagramSection section;

  @override
  Widget build(BuildContext context) {
    final items = section.items;
    if (items.isEmpty) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 28, 16, 4),
          child: SectionHeader(
            scriptWord: section.subtitle != null && section.subtitle!.isNotEmpty
                ? section.subtitle!.replaceFirst('@', '@')
                : '@estele.co',
            title: 'Styled by You #EsteleQueens',
          ),
        ),
        const SizedBox(height: 12),
        GridView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          padding: const EdgeInsets.symmetric(horizontal: 16),
          itemCount: items.length,
          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: 2,
            mainAxisSpacing: 10,
            crossAxisSpacing: 10,
          ),
          itemBuilder: (context, i) {
            final item = items[i];
            return InkWell(
              borderRadius: BorderRadius.circular(4),
              onTap: item.slug.isEmpty ? null : () => Navigator.of(context).pushNamed('/product/${item.slug}'),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(4),
                child: Stack(
                  fit: StackFit.expand,
                  children: [
                    AppImage(url: item.imageUrl),
                    DecoratedBox(
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment.topCenter,
                          end: Alignment.bottomCenter,
                          colors: [Colors.transparent, Colors.black.withValues(alpha: 0.45)],
                        ),
                      ),
                    ),
                    Align(
                      alignment: Alignment.bottomLeft,
                      child: Padding(
                        padding: const EdgeInsets.all(6),
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            if (item.title.isNotEmpty)
                              Text(
                                item.title,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: AppTypography.bodySmall(size: 10, color: Colors.white, weight: FontWeight.w600),
                              ),
                            Text(
                              _priceOf(item),
                              style: AppTypography.bodySmall(size: 10, color: AppColors.goldLight, weight: FontWeight.w600),
                            ),
                          ],
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
    );
  }

  static String _priceOf(Product product) {
    final value = product.compareAtPrice ?? product.price;
    return '₹${value.toStringAsFixed(0)}';
  }
}