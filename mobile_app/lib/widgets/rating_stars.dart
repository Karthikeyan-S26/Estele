import 'package:flutter/material.dart';

import '../theme/app_colors.dart';

/// Five-star rating row; renders filled/half/empty gold stars with an
/// optional review count next to them.
class RatingStars extends StatelessWidget {
  const RatingStars({
    super.key,
    this.rating,
    this.count,
    this.size = 14,
    this.showCount = true,
    this.color = AppColors.star,
  });

  final double? rating;
  final int? count;
  final double size;
  final bool showCount;
  final Color color;

  @override
  Widget build(BuildContext context) {
    final value = rating ?? 0;

    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        for (var i = 1; i <= 5; i++)
          Icon(
            i <= value.round()
                ? Icons.star_rounded
                : (i - value <= 0.5 && i - value > 0
                    ? Icons.star_half
                    : Icons.star_border_rounded),
            size: size,
            color: color,
          ),
        if (showCount && count != null) ...[
          const SizedBox(width: 4),
          Text(
            '($count)',
            style: TextStyle(fontSize: size - 3, color: AppColors.muted),
          ),
        ],
      ],
    );
  }
}