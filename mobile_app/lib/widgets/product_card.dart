import 'package:flutter/material.dart';

import '../models/product.dart';
import '../theme/app_colors.dart';
import '../theme/app_typography.dart';
import 'app_image.dart';
import 'price_text.dart';
import 'rating_stars.dart';

/// Product card used in grids, carousels and the wishlist — mirrors the web
/// card: image, name, price/sale, rating, wishlist toggle, and badges.
class ProductCard extends StatelessWidget {
  const ProductCard({
    super.key,
    required this.product,
    this.onTap,
    this.onWishlistTap,
    this.isWishlisted,
    this.compact = false,
    this.aspectRatio = 3 / 4,
  });

  final Product product;
  final VoidCallback? onTap;
  final VoidCallback? onWishlistTap;
  final bool? isWishlisted;
  final bool compact;
  final double aspectRatio;

  @override
  Widget build(BuildContext context) {
    final wishlisted = isWishlisted ?? false;
    final isNew = product.isNew;

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(4),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Image + badges + wishlist
          AspectRatio(
            aspectRatio: aspectRatio,
            child: Stack(
              fit: StackFit.expand,
              children: [
                AppImage(url: product.imageUrl, borderRadius: BorderRadius.circular(4)),
                // badges
                Positioned(
                  top: 8,
                  left: 8,
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      if (isNew)
                        _Badge(
                          label: 'NEW',
                          color: AppColors.newBadge,
                          foreground: Colors.white,
                        ),
                      if (product.inStock == false) ...[
                        const SizedBox(width: 4),
                        const _Badge(label: 'SOLD OUT', color: AppColors.soldOut, foreground: Colors.white),
                      ],
                    ],
                  ),
                ),
                // wishlist
                Positioned(
                  top: 6,
                  right: 6,
                  child: Material(
                    color: Colors.white.withValues(alpha: 0.9),
                    shape: const CircleBorder(),
                    child: InkWell(
                      customBorder: const CircleBorder(),
                      onTap: onWishlistTap,
                      child: Padding(
                        padding: const EdgeInsets.all(6),
                        child: Icon(
                          wishlisted ? Icons.favorite_rounded : Icons.favorite_outline_rounded,
                          size: 18,
                          color: wishlisted ? AppColors.accent : AppColors.muted,
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 8),
          // Info
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  product.title,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: compact ? AppTypography.bodySmall(size: 12, color: AppColors.ink) : AppTypography.body(size: 13.5),
                ),
                if (product.rating != null) ...[
                  const SizedBox(height: 2),
                  RatingStars(rating: product.rating, count: product.reviewCount, size: compact ? 11 : 12, showCount: false),
                ],
                const SizedBox(height: 3),
                PriceText(
                  price: product.price,
                  compareAtPrice: product.compareAtPrice,
                  discountPercent: product.effectiveDiscountPercent > 0 ? product.effectiveDiscountPercent : null,
                  size: compact ? 13 : 14,
                  small: true,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _Badge extends StatelessWidget {
  const _Badge({required this.label, required this.color, required this.foreground});

  final String label;
  final Color color;
  final Color foreground;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.92),
        borderRadius: BorderRadius.circular(2),
      ),
      child: Text(
        label,
        style: TextStyle(color: foreground, fontSize: 9, fontWeight: FontWeight.w700, letterSpacing: 0.8),
      ),
    );
  }
}