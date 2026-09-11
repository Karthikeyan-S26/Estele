import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../models/product.dart';
import '../../../providers/wishlist_provider.dart';
import '../../../widgets/product_card.dart';
import '../../../widgets/section_header.dart';

/// A horizontal product strip with an optional script-word + "View all" CTA —
/// used for Trending Now, New Arrivals and Bestsellers.
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

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 24, 16, 8),
          child: SectionHeader(
            scriptWord: scriptWord,
            title: title,
            onViewAll: onViewAll,
            viewAllLabel: viewAllLabel,
          ),
        ),
        SizedBox(
          height: 250,
          child: ListView.builder(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            scrollDirection: Axis.horizontal,
            itemCount: products.length,
            itemBuilder: (context, i) {
              final product = products[i];
              return SizedBox(
                width: 150,
                child: ProductCard(
                  product: product,
                  compact: true,
                  isWishlisted: wishlist.isWishlisted(product.id),
                  onWishlistTap: () => wishlist.toggle(product.id, product: product),
                  onTap: () => Navigator.of(context).pushNamed('/product/${product.slug}'),
                ),
              );
            },
          ),
        ),
      ],
    );
  }
}