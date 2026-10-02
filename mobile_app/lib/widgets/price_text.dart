import 'package:flutter/material.dart';

import '../theme/app_colors.dart';
import '../theme/app_typography.dart';
import '../utils/formatters.dart';

/// Renders a ₹ price with the optional compare-at strikethrough and discount
/// %, matching the web storefront's pricing presentation.
class PriceText extends StatelessWidget {
  const PriceText({
    super.key,
    required this.price,
    this.compareAtPrice,
    this.discountPercent,
    this.size = 15,
    this.small = false,
    this.showDiscountBadge = true,
  });

  final double price;
  final double? compareAtPrice;
  final int? discountPercent;
  final double size;
  final bool small;
  final bool showDiscountBadge;

  @override
  Widget build(BuildContext context) {
    final hasSale = compareAtPrice != null && compareAtPrice! > price;
    final discount =
        discountPercent ??
        (hasSale
            ? ((compareAtPrice! - price) / compareAtPrice! * 100).round()
            : 0);

    return Row(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        Text(
          formatINR(price),
          style: (hasSale ? AppTypography.salePrice : AppTypography.price)(
            size: size,
          ),
        ),
        if (hasSale) ...[
          const SizedBox(width: 6),
          Text(
            formatINR(compareAtPrice!),
            style: AppTypography.bodySmall(
              size: small ? 11 : 12,
              color: AppColors.muted,
            ).copyWith(decoration: TextDecoration.lineThrough),
          ),
        ],
        if (hasSale && showDiscountBadge) ...[
          const SizedBox(width: 6),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
            decoration: BoxDecoration(
              color: AppColors.pinkSoft,
              borderRadius: BorderRadius.circular(2),
            ),
            child: Text(
              '$discount% off',
              style: AppTypography.bodySmall(
                size: small ? 10 : 11,
                color: AppColors.sale,
                weight: FontWeight.w700,
              ),
            ),
          ),
        ],
      ],
    );
  }
}
