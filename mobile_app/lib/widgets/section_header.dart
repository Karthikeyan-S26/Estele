import 'package:flutter/material.dart';

import '../theme/app_colors.dart';
import '../theme/app_typography.dart';

/// Mirrors `resources/views/components/section-header.blade.php`:
///
///  - `centered: true`  → the default `align="center"` block used by
///    shop-by-category, collection-carousel, testimonials, usp and
///    shop-the-look: centered eyebrow → uppercase title → gold rule →
///    (subtitle) → (centered CTA with gold bottom border).
///  - `centered: false` → the `align="left"` block used by product-carousel,
///    faq and journal: eyebrow → uppercase title, "View all" CTA on the right.
class SectionHeader extends StatelessWidget {
  const SectionHeader({
    super.key,
    required this.title,
    this.scriptWord,
    this.onViewAll,
    this.viewAllLabel = 'View all',
    this.centered = false,
  });

  final String title;
  final String? scriptWord;
  final VoidCallback? onViewAll;
  final String viewAllLabel;
  final bool centered;

  @override
  Widget build(BuildContext context) {
    if (centered) return _buildCentered();

    return Row(
      crossAxisAlignment: CrossAxisAlignment.end,
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              if (scriptWord != null)
                Padding(
                  padding: const EdgeInsets.only(bottom: 6), // mb 1.5×4
                  child: Text(
                    scriptWord!.toUpperCase(),
                    style: AppTypography.eyebrow(),
                  ),
                ),
              Text(title.toUpperCase(), style: AppTypography.sectionTitle()),
            ],
          ),
        ),
        if (onViewAll != null)
          GestureDetector(
            behavior: HitTestBehavior.opaque,
            onTap: onViewAll,
            child: Padding(
              padding: const EdgeInsets.only(left: 12),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    viewAllLabel.toUpperCase(),
                    style: AppTypography.button(
                      size: 11,
                      color: AppColors.heading,
                      letterSpacing: 11 * 0.14,
                    ),
                  ),
                  const SizedBox(width: 4),
                  const Icon(
                    Icons.arrow_forward_ios_rounded,
                    size: 11,
                    color: AppColors.heading,
                  ),
                ],
              ),
            ),
          ),
      ],
    );
  }

  /// Centered variant — `.section-head` (mb-5 = 20px is applied by callers).
  Widget _buildCentered() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        if (scriptWord != null)
          Padding(
            padding: const EdgeInsets.only(bottom: 6), // mb 1.5×4
            child: Text(
              scriptWord!.toUpperCase(),
              textAlign: TextAlign.center,
              style: AppTypography.eyebrow(),
            ),
          ),
        Text(
          title.toUpperCase(),
          textAlign: TextAlign.center,
          style: AppTypography.sectionTitle(),
        ),
        // section-head__rule: 48px × 1px gold, mt-3 = 12px.
        Container(
          margin: const EdgeInsets.only(top: 12),
          height: 1,
          width: 48, // calc(spacing) * 12
          color: AppColors.gold,
        ),
        if (onViewAll != null)
          Padding(
            padding: const EdgeInsets.only(top: 16),
            child: GestureDetector(
              behavior: HitTestBehavior.opaque,
              onTap: onViewAll,
              child: Container(
                padding: const EdgeInsets.only(bottom: 4),
                decoration: const BoxDecoration(
                  border: Border(bottom: BorderSide(color: AppColors.gold)),
                ),
                child: Text(
                  viewAllLabel.toUpperCase(),
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
      ],
    );
  }
}
