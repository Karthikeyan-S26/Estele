import 'package:flutter/material.dart';

import '../../../models/category.dart';
import '../../../theme/app_colors.dart';
import '../../../theme/app_typography.dart';
import '../../../widgets/app_image.dart';
import '../../../widgets/section_header.dart';
import '../category_products_screen.dart';

/// "Curated Selections → Shop by Category": a row of circle-cropped category
/// tiles from the backend Category model.
class CategoryStrip extends StatelessWidget {
  const CategoryStrip({super.key, required this.categories});

  final List<Category> categories;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Padding(
          padding: EdgeInsets.fromLTRB(16, 24, 16, 4),
          child: SectionHeader(scriptWord: 'Curated Selections', title: 'Shop by Category'),
        ),
        const SizedBox(height: 8),
        SizedBox(
          height: 116,
          child: ListView.separated(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            scrollDirection: Axis.horizontal,
            itemCount: categories.length,
            separatorBuilder: (_, __) => const SizedBox(width: 16),
            itemBuilder: (context, i) {
              final category = categories[i];
              return InkWell(
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
                child: SizedBox(
                  width: 68,
                  child: Column(
                    children: [
                      Container(
                        width: 64,
                        height: 64,
                        decoration: const BoxDecoration(color: AppColors.warmBeige, shape: BoxShape.circle),
                        clipBehavior: Clip.antiAlias,
                        child: AppImage(url: category.imageUrl),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        category.name,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        textAlign: TextAlign.center,
                        style: AppTypography.bodySmall(
                          size: 10.5,
                          color: AppColors.ink,
                          weight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
        ),
      ],
    );
  }
}