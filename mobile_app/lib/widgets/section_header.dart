import 'package:flutter/material.dart';

import '../theme/app_colors.dart';
import '../theme/app_typography.dart';

/// A thin "label + divider" section header used across Home/Category/Journey
/// rows. Optional trailing action navigates to a listing.
class SectionHeader extends StatelessWidget {
  const SectionHeader({
    super.key,
    required this.title,
    this.scriptWord,
    this.onViewAll,
    this.viewAllLabel = 'View all',
  });

  final String title;
  final String? scriptWord;
  final VoidCallback? onViewAll;
  final String viewAllLabel;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.end,
      children: [
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (scriptWord != null)
              Text(scriptWord!, style: AppTypography.scriptAccent(size: 30)),
            Text(
              title,
              style: AppTypography.sectionTitle(size: 18),
            ),
          ],
        ),
        const Spacer(),
        if (onViewAll != null)
          TextButton(
            onPressed: onViewAll,
            child: Text(viewAllLabel,
                style: AppTypography.label(size: 12, color: AppColors.accentDark, letterSpacing: 1.2)),
          ),
      ],
    );
  }
}