import 'package:flutter/material.dart';

import '../../../models/home_data.dart';
import '../../../theme/app_colors.dart';
import '../../../theme/app_typography.dart';
import '../../../widgets/app_image.dart';

/// "As Seen On → Celebrities": a dark band with the celebrity cards (names +
/// media photos) from the CMS block.
class CelebrityStrip extends StatelessWidget {
  const CelebrityStrip({super.key, required this.celebrities, this.subtitle});

  final List<Celebrity> celebrities;
  final String? subtitle;

  @override
  Widget build(BuildContext context) {
    if (celebrities.isEmpty) return const SizedBox.shrink();

    return Container(
      margin: const EdgeInsets.fromLTRB(0, 28, 0, 0),
      padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 16),
      color: AppColors.deepWine,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'As Seen On',
            style: AppTypography.scriptAccent(size: 30, color: AppColors.goldLight),
          ),
          Text(
            'Celebrities',
            style: AppTypography.sectionTitle(size: 20, color: Colors.white),
          ),
          if (subtitle != null) ...[
            const SizedBox(height: 4),
            Text(
              subtitle!,
              style: AppTypography.bodySmall(size: 12, color: Colors.white70),
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
          ],
          const SizedBox(height: 16),
          SizedBox(
            height: 196,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              itemCount: celebrities.length,
              separatorBuilder: (_, __) => const SizedBox(width: 12),
              itemBuilder: (context, i) {
                final celebrity = celebrities[i];
                return SizedBox(
                  width: 128,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Container(
                        height: 160,
                        width: double.infinity,
                        clipBehavior: Clip.antiAlias,
                        decoration: BoxDecoration(
                          color: AppColors.deepWine,
                          borderRadius: BorderRadius.circular(4),
                          border: Border.all(color: AppColors.gold.withValues(alpha: 0.4), width: 0.6),
                        ),
                        child: AppImage(url: celebrity.image),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        celebrity.title,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: AppTypography.bodySmall(size: 11.5, color: Colors.white, weight: FontWeight.w600),
                      ),
                    ],
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}