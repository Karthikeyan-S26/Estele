import 'package:flutter/material.dart';

import '../../../models/collection.dart';
import '../../../theme/app_colors.dart';
import '../../../theme/app_typography.dart';
import '../../../widgets/app_image.dart';
import '../../../widgets/section_header.dart';
import '../category_products_screen.dart';

/// "Signature Edits → Shop by Collection": a 2-column grid of all active
/// collections (image + name + product count), each tappable into its listing.
class CollectionsGrid extends StatelessWidget {
  const CollectionsGrid({super.key, required this.collections});

  final List<Collection> collections;

  @override
  Widget build(BuildContext context) {
    if (collections.isEmpty) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Padding(
          padding: EdgeInsets.fromLTRB(16, 24, 16, 8),
          child: SectionHeader(scriptWord: 'Signature Edits', title: 'Shop by Collection'),
        ),
        GridView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          padding: const EdgeInsets.symmetric(horizontal: 16),
          itemCount: collections.length,
          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: 2,
            mainAxisSpacing: 12,
            crossAxisSpacing: 12,
            childAspectRatio: 1.05,
          ),
          itemBuilder: (context, i) {
            final collection = collections[i];
            return InkWell(
              borderRadius: BorderRadius.circular(4),
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
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(4),
                      child: SizedBox(
                        width: double.infinity,
                        child: Stack(
                          fit: StackFit.expand,
                          children: [
                            AppImage(url: collection.imageUrl),
                            if (collection.productCount > 0)
                              Positioned(
                                right: 6,
                                top: 6,
                                child: Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                  decoration: BoxDecoration(
                                    color: Colors.white.withValues(alpha: 0.92),
                                    borderRadius: BorderRadius.circular(2),
                                  ),
                                  child: Text(
                                    '${collection.productCount} designs',
                                    style: AppTypography.bodySmall(size: 9.5, color: AppColors.ink, weight: FontWeight.w600),
                                  ),
                                ),
                              ),
                          ],
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    collection.name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: AppTypography.body(size: 12.5, color: AppColors.ink, weight: FontWeight.w600),
                  ),
                ],
              ),
            );
          },
        ),
      ],
    );
  }
}