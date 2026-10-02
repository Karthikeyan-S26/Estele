import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../models/product.dart';
import '../providers/auth_provider.dart';
import '../providers/cart_provider.dart';
import '../providers/checkout_intent.dart';
import '../theme/app_colors.dart';
import '../theme/app_typography.dart';
import 'app_image.dart';
import 'price_text.dart';

/// Product card matching the live website's shared `x-product-card`
/// (used by home strips AND listing grids alike):
///  - white card, soft border, rounded-xl, column layout;
///  - square image inside an 8px inset frame (radius-lg, paper placeholder);
///  - "X% off" sale badge top-left, round wishlist circle top-right;
///  - serif product name (line-clamp-2), price row (sale + strike-through
///    MRP), then the bottom-docked Buy Now (outline) + Add to Bag (filled)
///    CTA row — quantity 1, base product, exactly like the site's card form.
///
/// [compact] is the slim variant for horizontal strips and the wishlist grid
/// (image 4:5, no card border) with the same CTA row.
class ProductCard extends StatelessWidget {
  const ProductCard({
    super.key,
    required this.product,
    this.onTap,
    this.onWishlistTap,
    this.isWishlisted,
    this.compact = false,
    this.onAddToBag,
    this.onBuyNow,
  });

  final Product product;
  final VoidCallback? onTap;
  final VoidCallback? onWishlistTap;
  final bool? isWishlisted;
  final bool compact;

  /// Explicit add-to-bag handler (wins over the built-in one below).
  final VoidCallback? onAddToBag;

  /// Explicit buy-now handler (wins over the built-in one below).
  final VoidCallback? onBuyNow;

  /// Website card-form equivalent: quantity 1 of the base product through
  /// the shared CartProvider, with the same success/error snackbar.
  Future<void> _defaultAdd(BuildContext context) async {
    final err = await context.read<CartProvider>().addItem(
          productId: product.id,
          productSlug: product.slug,
          quantity: 1,
        );
    if (!context.mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(err ?? 'Added to your bag')),
    );
  }

  /// Website express equivalent: same add, then straight to checkout. Guests
  /// fold through the login → OTP round first; the pending purchase (already
  /// sitting in the guest cart, merged server-side at sign-in) is carried back
  /// into checkout by [CheckoutIntent].
  Future<void> _defaultBuyNow(BuildContext context) async {
    final err = await context.read<CartProvider>().addItem(
          productId: product.id,
          productSlug: product.slug,
          quantity: 1,
        );
    if (!context.mounted) return;
    if (err != null) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(err)));
      return;
    }
    final authed = context.read<AuthProvider>().isAuthenticated;
    if (authed) {
      Navigator.of(context).pushNamed('/checkout');
    } else {
      CheckoutIntent.arm();
      Navigator.of(context).pushNamed('/login');
    }
  }

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
                    // Rating pill — website's star+rating badge over the
                    // image bottom edge (shown only when reviews exist).
                    _RatingPill(
                      product: product,
                      soldOut: product.inStock == false,
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
                  // 2-line cap bounds the title; the price follows it
                  // directly and the CTA row docks at the bottom (website
                  // `mt-auto` form), so grid heights stay consistent with
                  // no squeeze and no blank gap.
                  Text(
                    product.title,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: AppTypography.editorial(
                      size: 13,
                      weight: FontWeight.w500,
                      height: 1.3,
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
                  const SizedBox(height: 8),
                  _CtaRow(
                    onBuyNow: onBuyNow ?? () => _defaultBuyNow(context),
                    onAddToBag: onAddToBag ?? () => _defaultAdd(context),
                  ),
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
                _RatingPill(
                  product: product,
                  soldOut: product.inStock == false,
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
                Text(
                  product.title,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: AppTypography.editorial(
                    size: 12,
                    weight: FontWeight.w500,
                    height: 1.3,
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
                const Spacer(),
                const SizedBox(height: 6),
                _CtaRow(
                  onBuyNow: onBuyNow ?? () => _defaultBuyNow(context),
                  onAddToBag: onAddToBag ?? () => _defaultAdd(context),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// Website card CTA row (`form.mt-auto.flex.gap-1.5`): outline Buy Now +
/// filled Add to Bag, both flex-1, 36px tall, 12px labels.
class _CtaRow extends StatelessWidget {
  const _CtaRow({required this.onBuyNow, required this.onAddToBag});

  final VoidCallback onBuyNow;
  final VoidCallback onAddToBag;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: OutlinedButton(
            onPressed: onBuyNow,
            style: OutlinedButton.styleFrom(
              minimumSize: const Size.fromHeight(36),
              side: const BorderSide(color: AppColors.accent, width: 1.5),
              foregroundColor: AppColors.accent,
              padding: EdgeInsets.zero,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(6),
              ),
            ),
            child: Text(
              'Buy Now',
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: AppTypography.button(
                size: 12,
                color: null,
                letterSpacing: 0.3,
              ),
            ),
          ),
        ),
        const SizedBox(width: 6),
        Expanded(
          child: FilledButton(
            onPressed: onAddToBag,
            style: FilledButton.styleFrom(
              backgroundColor: AppColors.accentDark,
              minimumSize: const Size.fromHeight(36),
              padding: EdgeInsets.zero,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(6),
              ),
              elevation: 0,
            ),
            child: Text(
              'Add to Bag',
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: AppTypography.button(size: 12, letterSpacing: 0.3),
            ),
          ),
        ),
      ],
    );
  }
}

/// Website parity rating pill (`product-card` star badge): white/90 rounded
/// pill with a gold star + one-decimal rating, over the image's bottom
/// edge. Rendered only when the product actually has reviews — never an
/// empty pill. Sits bottom-left, or bottom-right when SOLD OUT occupies
/// the left corner.
class _RatingPill extends StatelessWidget {
  const _RatingPill({required this.product, required this.soldOut});

  final Product product;
  final bool soldOut;

  @override
  Widget build(BuildContext context) {
    final rating = product.rating;
    if (product.reviewCount <= 0 || rating == null) {
      return const SizedBox.shrink();
    }

    return Positioned(
      left: soldOut ? null : 8,
      right: soldOut ? 8 : null,
      bottom: 8,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.9),
          borderRadius: BorderRadius.circular(999),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.star_rounded, size: 12, color: AppColors.gold),
            const SizedBox(width: 3),
            Text(
              rating.toStringAsFixed(1),
              style: const TextStyle(
                color: AppColors.heading,
                fontSize: 11,
                fontWeight: FontWeight.w700,
                height: 1,
              ),
            ),
          ],
        ),
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
