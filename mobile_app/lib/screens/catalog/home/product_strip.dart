import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../models/product.dart';
import '../../../providers/cart_provider.dart';
import '../../../providers/wishlist_provider.dart';
import '../../../widgets/product_card.dart';
import '../../../widgets/section_header.dart';

/// Product section — mirrors `home/blocks/product-carousel.blade.php`:
///  - `section py-6 md:py-9`, content `px-3 md:px-4`;
///  - `align="left"` section-head (eyebrow → title + "View all" CTA);
///  - `grid grid-cols-2 gap-2.5` of full web-style cards — two per mobile
///    viewport (3 at sm, 4 at md), capped at 10 cards like the blade's
///    `->take(10)`.
class ProductStrip extends StatelessWidget {
  const ProductStrip({
    super.key,
    required this.products,
    this.scriptWord,
    required this.title,
    this.onViewAll,
    this.viewAllLabel = 'View all',
  });

  final List<Product> products;
  final String? scriptWord;
  final String title;
  final VoidCallback? onViewAll;
  final String viewAllLabel;

  @override
  Widget build(BuildContext context) {
    if (products.isEmpty) return const SizedBox.shrink();

    final wishlist = context.watch<WishlistProvider>();
    final cart = context.read<CartProvider>();

    return Container(
      // section py-6 = 24px, content px-3 = 12px
      padding: const EdgeInsets.symmetric(vertical: 24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(12, 0, 12, 16),
            child: SectionHeader(
              scriptWord: scriptWord,
              title: title,
              onViewAll: onViewAll,
              viewAllLabel: viewAllLabel,
            ),
          ),
          LayoutBuilder(
            builder: (context, constraints) {
              // px-3 both sides, gap-2.5 (10px) between the two columns.
              final tileWidth =
                  (constraints.maxWidth - 24 - 10) / 2;
              // Card height ≈ square image (tileWidth − 2×8px frame) + body
              // (title 2 lines, price, CTA). Rows auto-size on the web; we
              // budget for the tallest (2-line title + CTA) card.
              final cardHeight = tileWidth - 16 + 124;
              return GridView.builder(
                padding: const EdgeInsets.symmetric(horizontal: 12),
                physics: const NeverScrollableScrollPhysics(),
                shrinkWrap: true,
                gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 2, // grid-cols-2 mobile
                  crossAxisSpacing: 10, // gap-2.5 = 10px
                  mainAxisSpacing: 10,
                  mainAxisExtent: cardHeight,
                ),
                itemCount: products.take(10).length, // ->take(10)
                itemBuilder: (context, i) {
                  final product = products[i];
                  return ProductCard(
                    product: product,
                    isWishlisted: wishlist.isWishlisted(product.id),
                    onWishlistTap: () => wishlist.toggle(
                      product.id,
                      product: product,
                    ),
                    onTap: () => Navigator.of(
                      context,
                    ).pushNamed('/product/${product.slug}'),
                    onAddToBag: product.hasVariants
                        ? () => Navigator.of(
                            context,
                          ).pushNamed('/product/${product.slug}')
                        : product.inStock
                        ? () async {
                            final error = await cart.addItem(
                              productId: product.id,
                              productSlug: product.slug,
                            );
                            if (!context.mounted) return;
                            ScaffoldMessenger.of(context)
                              ..hideCurrentSnackBar()
                              ..showSnackBar(
                                SnackBar(
                                  content: Text(error ?? 'Added to bag'),
                                  duration: const Duration(seconds: 2),
                                ),
                              );
                          }
                        : null,
                  );
                },
              );
            },
          ),
        ],
      ),
    );
  }
}