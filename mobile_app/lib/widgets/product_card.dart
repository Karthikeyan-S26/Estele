import 'package:flutter/material.dart';

import '../models/product.dart';
import '../theme/app_colors.dart';
import '../theme/app_typography.dart';
import 'app_image.dart';
import 'price_text.dart';

/// Product card matching the live website's card:
///  - white card, soft border, rounded-xl, column layout;
///  - square image inside an 8px inset frame (radius-lg, paper placeholder);
///  - "X% off" sale badge top-left, round wishlist circle top-right;
///  - serif product name (line-clamp-2), price row (sale + strike-through MRP);
///  - full-width dark "Add to cart" CTA (shown when [onAddToBag] is provided).
///
/// [compact] is the slim variant for horizontal strips and the wishlist grid:
/// image 4:5, no card border, no CTA — it leaves room for a two-line name.
class ProductCard extends StatelessWidget {
  const ProductCard({
    super.key,
    required this.product,
    this.onTap,
    this.onWishlistTap,
    this.isWishlisted,
    this.compact = false,
    this.onAddToBag,
  });

  final Product product;
  final VoidCallback? onTap;
  final VoidCallback? onWishlistTap;
  final bool? isWishlisted;
  final bool compact;

  /// When set, renders the full-width "Add to cart" button (web card style).
  /// Should be wired to [CartProvider.addItem] by the caller.
  final VoidCallback? onAddToBag;

  @override
  Widget build(BuildContext context) {
    final wishlisted = isWishlisted ?? false;
    final salePercent = product.effectiveDiscountPercent;

    if (compact) return _buildCompact(context);

    return Container(
      decoration: BoxDecoration(
        color: AppColors.paper,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.line),
      ),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Image frame — 8px inset, square, paper placeholder
            Padding(
              padding: const EdgeInsets.all(8),
              child: AspectRatio(
                aspectRatio: 1,
                child: Stack(
                  fit: StackFit.expand,
                  children: [
                    AppImage(
                      url: product.imageUrl,
                      borderRadius: BorderRadius.circular(8),
                      placeholderColor: AppColors.greySoft,
                    ),
                    // Sale badge — top-left
                    if (product.isOnSale)
                      Positioned(
                        left: 8,
                        top: 8,
                        child: _Badge(
                          label: '$salePercent% off',
                          color: AppColors.sale,
                          foreground: Colors.white,
                        ),
                      )
                    else if (product.isNew)
                      Positioned(
                        left: 8,
                        top: 8,
                        child: _Badge(
                          label: 'NEW',
                          color: AppColors.newBadge,
                          foreground: Colors.white,
                        ),
                      ),
                    if (product.inStock == false)
                      Positioned(
                        left: 8,
                        bottom: 8,
                        child: _Badge(
                          label: 'SOLD OUT',
                          color: AppColors.soldOut,
                          foreground: Colors.white,
                        ),
                      ),
                    // Wishlist — round white circle top-right
                    Positioned(
                      top: 8,
                      right: 8,
                      child: Material(
                        color: Colors.white.withValues(alpha: 0.95),
                        shape: const CircleBorder(),
                        elevation: 1,
                        child: InkWell(
                          customBorder: const CircleBorder(),
                          onTap: onWishlistTap,
                          child: Padding(
                            padding: const EdgeInsets.all(7),
                            child: Icon(
                              wishlisted
                                  ? Icons.favorite_rounded
                                  : Icons.favorite_outline_rounded,
                              size: 17,
                              color: wishlisted
                                  ? AppColors.accent
                                  : AppColors.heading,
                            ),
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            // Body — title + price + CTA
            Expanded(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(12, 2, 12, 12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Flexible(
                      child: Text(
                        product.title,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: AppTypography.editorial(
                          size: 13,
                          weight: FontWeight.w500,
                          height: 1.3,
                        ),
                      ),
                    ),
                    const SizedBox(height: 6),
                    PriceText(
                      price: product.price,
                      compareAtPrice: product.compareAtPrice,
                      discountPercent: salePercent > 0 ? salePercent : null,
                      size: 14,
                      small: true,
                      showDiscountBadge: false,
                    ),
                    const Spacer(),
                    if (onAddToBag != null) ...[
                      const SizedBox(height: 8),
                      SizedBox(
                        width: double.infinity,
                        child: InkWell(
                          onTap: product.inStock ? onAddToBag : null,
                          borderRadius: BorderRadius.circular(6),
                          child: Container(
                            padding: const EdgeInsets.symmetric(vertical: 10),
                            decoration: BoxDecoration(
                              color: product.inStock
                                  ? AppColors.heading
                                  : AppColors.greySoft,
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: const Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(
                                  Icons.shopping_bag_outlined,
                                  size: 14,
                                  color: Colors.white,
                                ),
                                SizedBox(width: 6),
                                Text(
                                  'ADD TO CART',
                                  style: TextStyle(
                                    color: Colors.white,
                                    fontSize: 10,
                                    fontWeight: FontWeight.w600,
                                    letterSpacing: 0.6,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildCompact(BuildContext context) {
    final wishlisted = isWishlisted ?? false;
    final salePercent = product.effectiveDiscountPercent;

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Image 4:5 + badges + wishlist
          AspectRatio(
            aspectRatio: 4 / 5,
            child: Stack(
              fit: StackFit.expand,
              children: [
                AppImage(
                  url: product.imageUrl,
                  borderRadius: BorderRadius.circular(8),
                ),
                if (product.isOnSale)
                  Positioned(
                    left: 8,
                    top: 8,
                    child: _Badge(
                      label: '$salePercent% off',
                      color: AppColors.sale,
                      foreground: Colors.white,
                    ),
                  )
                else if (product.isNew)
                  Positioned(
                    left: 8,
                    top: 8,
                    child: _Badge(
                      label: 'NEW',
                      color: AppColors.newBadge,
                      foreground: Colors.white,
                    ),
                  ),
                if (product.inStock == false)
                  Positioned(
                    left: 8,
                    bottom: 8,
                    child: _Badge(
                      label: 'SOLD OUT',
                      color: AppColors.soldOut,
                      foreground: Colors.white,
                    ),
                  ),
                Positioned(
                  top: 8,
                  right: 8,
                  child: Material(
                    color: Colors.white.withValues(alpha: 0.95),
                    shape: const CircleBorder(),
                    elevation: 1,
                    child: InkWell(
                      customBorder: const CircleBorder(),
                      onTap: onWishlistTap,
                      child: Padding(
                        padding: const EdgeInsets.all(6),
                        child: Icon(
                          wishlisted
                              ? Icons.favorite_rounded
                              : Icons.favorite_outline_rounded,
                          size: 16,
                          color: wishlisted
                              ? AppColors.accent
                              : AppColors.heading,
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Flexible(
                  child: Text(
                    product.title,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: AppTypography.editorial(
                      size: 12,
                      weight: FontWeight.w500,
                      height: 1.3,
                    ),
                  ),
                ),
                const SizedBox(height: 3),
                PriceText(
                  price: product.price,
                  compareAtPrice: product.compareAtPrice,
                  discountPercent: salePercent > 0 ? salePercent : null,
                  size: 13,
                  small: true,
                  showDiscountBadge: false,
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
  const _Badge({
    required this.label,
    required this.color,
    required this.foreground,
  });

  final String label;
  final Color color;
  final Color foreground;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 3),
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(4),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: foreground,
          fontSize: 9,
          fontWeight: FontWeight.w700,
          letterSpacing: 0.5,
        ),
      ),
    );
  }
}
