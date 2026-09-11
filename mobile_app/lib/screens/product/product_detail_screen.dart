import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../data/repositories/catalog_repository.dart';
import '../../models/product.dart';
import '../../models/review.dart';
import '../../providers/cart_provider.dart';
import '../../providers/wishlist_provider.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_typography.dart';
import '../../widgets/app_image.dart';
import '../../widgets/load_state.dart';
import '../../widgets/price_text.dart';
import '../../widgets/product_card.dart';
import '../../widgets/quantity_stepper.dart';
import '../../widgets/rating_stars.dart';

class ProductDetailScreen extends StatefulWidget {
  const ProductDetailScreen({super.key, required this.slug});

  final String slug;

  @override
  State<ProductDetailScreen> createState() => _ProductDetailScreenState();
}

class _ProductDetailScreenState extends State<ProductDetailScreen> {
  Product? _product;
  List<Product> _related = [];
  double? _avgRating;
  int _reviewCount = 0;
  List<Review> _reviews = [];
  bool _loading = true;
  bool _failed = false;

  int _quantity = 1;
  String? _selectedSku;
  List<String> _sizes = const [];
  int _selectedSizeIndex = -1;
  bool _addingToCart = false;

  final _pageController = PageController();

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _failed = false;
    });
    try {
      final result = await CatalogRepository.product(widget.slug);
      if (!mounted) return;
      setState(() {
        _product = result.page.product;
        _related = result.related;
        _avgRating = result.page.ratio;
        _reviewCount = result.page.count;
        _reviews = result.page.reviews
            .map((e) => Review.fromJson(e))
            .toList();
        _initVariants(result.page.product);
        _loading = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _failed = true;
        _loading = false;
      });
    }
  }

  void _initVariants(Product product) {
    final attrs = <String>{};
    for (final v in product.variants) {
      for (final value in v.attributes.values) {
        attrs.add(value.toString());
      }
    }
    _sizes = attrs.toList();
    if (_sizes.isNotEmpty) {
      _selectedSku = product.variants.where((v) => v.inStock).isNotEmpty
          ? product.variants.firstWhere((v) => v.inStock, orElse: () => product.variants.first).sku
          : product.variants.first.sku;
      _selectedSizeIndex = _sizes.indexOf(
        product.variants.first.attributes.values.firstOrNull ?? '',
      );
    }
  }

  Future<void> _addToCart(CartProvider cart) async {
    final product = _product!;
    if (product.inStock == false) return;

    setState(() => _addingToCart = true);
    String? error;
    if (product.hasVariants) {
      final variant = product.variants
          .where((v) => v.sku == _selectedSku)
          .firstOrNull;
      error = await cart.addItem(
        productId: product.id,
        productSlug: product.slug,
        variantId: variant?.id,
        quantity: _quantity,
      );
    } else {
      error = await cart.addItem(productId: product.id, productSlug: product.slug, quantity: _quantity);
    }
    if (!mounted) return;
    setState(() {
      _addingToCart = false;
      if (error == null) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Added to your bag')),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(error)),
        );
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Scaffold(body: LoadState.loading());
    }
    if (_failed || _product == null) {
      return Scaffold(
        appBar: AppBar(),
        body: LoadState.error(message: 'Could not load this piece.', onRetry: _load),
      );
    }

    final product = _product!;
    final wishlist = context.watch<WishlistProvider>();
    final cart = context.watch<CartProvider>();

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            pinned: true,
            expandedHeight: 340,
            backgroundColor: AppColors.ivory,
            foregroundColor: AppColors.heading,
            actions: [
              IconButton(
                icon: Icon(
                  wishlist.isWishlisted(product.id)
                      ? Icons.favorite_rounded
                      : Icons.favorite_outline_rounded,
                  color: wishlist.isWishlisted(product.id) ? AppColors.accent : AppColors.heading,
                ),
                onPressed: () => wishlist.toggle(product.id, product: product),
              ),
              IconButton(
                icon: const Icon(Icons.share_outlined),
                onPressed: () {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Share link copied')),
                  );
                },
              ),
            ],
            flexibleSpace: FlexibleSpaceBar(
              background: Stack(
                fit: StackFit.expand,
                children: [
                  PageView.builder(
                    controller: _pageController,
                    itemCount: product.gallery.isEmpty ? 1 : product.gallery.length,
                    itemBuilder: (context, i) {
                      if (product.gallery.isEmpty) {
                        return AppImage(url: product.imageUrl);
                      }
                      return AppImage(url: product.gallery[i].url);
                    },
                  ),
                  if (product.gallery.length > 1)
                    Positioned(
                      bottom: 10,
                      left: 0,
                      right: 0,
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: List.generate(product.gallery.length, (i) {
                          return AnimatedBuilder(
                            animation: _pageController,
                            builder: (_, __) {
                              final selected = (_pageController.page ?? 0).round() == i;
                              return Container(
                                width: selected ? 12 : 6,
                                height: 6,
                                margin: const EdgeInsets.symmetric(horizontal: 3),
                                decoration: BoxDecoration(
                                  color: selected ? AppColors.accent : AppColors.lineStrong,
                                  borderRadius: BorderRadius.circular(3),
                                ),
                              );
                            },
                          );
                        }),
                      ),
                    ),
                ],
              ),
            ),
          ),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // badges
                  Row(
                    children: [
                      if (product.isOnSale) _InfoBadge('SALE', AppColors.sale),
                      if (product.isNew) ...[
                        const SizedBox(width: 8),
                        _InfoBadge('NEW', AppColors.newBadge),
                      ],
                    ],
                  ),
                  const SizedBox(height: 10),
                  Text(product.title, style: AppTypography.editorial(size: 24)),
                  const SizedBox(height: 6),
                  if (_avgRating != null)
                    RatingStars(rating: _avgRating, count: _reviewCount, size: 16)
                  else
                    Text('New arrival', style: AppTypography.bodySmall(size: 12)),
                  const SizedBox(height: 10),
                  PriceText(
                    price: product.price,
                    compareAtPrice: product.compareAtPrice,
                    discountPercent: product.effectiveDiscountPercent > 0 ? product.effectiveDiscountPercent : null,
                    size: 22,
                  ),
                  const SizedBox(height: 10),
                  if (product.inStock == false)
                    Text('Currently out of stock', style: AppTypography.bodyMedium(color: AppColors.sale)),
                  const SizedBox(height: 4),
                  Text('SKU ${product.sku}', style: AppTypography.bodySmall(size: 11)),

                  // Variant size selector
                  if (_sizes.isNotEmpty) ...[
                    const SizedBox(height: 18),
                    Text('Select size', style: AppTypography.label(letterSpacing: 1.2)),
                    const SizedBox(height: 10),
                    Wrap(
                      spacing: 8,
                      children: List.generate(_sizes.length, (i) {
                        final selected = i == _selectedSizeIndex;
                        return ChoiceChip(
                          label: Text(_sizes[i]),
                          selected: selected,
                          onSelected: (_) {
                            setState(() {
                              _selectedSizeIndex = i;
                              final matching = product.variants
                                  .where((v) => v.attributes.values.contains(_sizes[i]))
                                  .toList();
                              if (matching.isNotEmpty) {
                                _selectedSku = matching.any((v) => v.inStock)
                                    ? matching.firstWhere((v) => v.inStock).sku
                                    : matching.first.sku;
                              }
                            });
                          },
                        );
                      }),
                    ),
                  ],

                  // Quantity + add
                  const SizedBox(height: 18),
                  Row(
                    children: [
                      QuantityStepper(
                        quantity: _quantity,
                        onChanged: (q) => setState(() => _quantity = q),
                        max: 10,
                        enabled: product.inStock,
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: FilledButton.icon(
                          onPressed: product.inStock && !_addingToCart
                              ? () => _addToCart(cart)
                              : null,
                          icon: _addingToCart
                              ? const SizedBox(
                                  width: 16,
                                  height: 16,
                                  child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                                )
                              : const Icon(Icons.shopping_bag_outlined, size: 18),
                          label: Text(product.inStock ? 'Add to bag' : 'Out of stock'),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  TextButton.icon(
                    onPressed: () {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Free shipping above ₹999 · Easy 30-day returns')),
                      );
                    },
                    icon: const Icon(Icons.local_shipping_outlined, size: 16),
                    label: const Text('Free shipping over ₹999 · Easy returns'),
                  ),

                  const Divider(height: 32),

                  // Description
                  Text('About this piece', style: AppTypography.sectionTitle(size: 17)),
                  const SizedBox(height: 10),
                  Text(
                    product.description ?? 'A timeless Estele creation.',
                    style: AppTypography.body(size: 14, color: AppColors.ink),
                  ),

                  // Reviews
                  if (_reviews.isNotEmpty) ...[
                    const Divider(height: 32),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text('Reviews', style: AppTypography.sectionTitle(size: 17)),
                        if (_reviewCount > 0)
                          Text('$_reviewCount', style: AppTypography.bodySmall()),
                      ],
                    ),
                    const SizedBox(height: 12),
                    ..._reviews.take(3).map((r) => _ReviewTile(review: r)),
                  ],

                  // Related
                  if (_related.isNotEmpty) ...[
                    const Divider(height: 32),
                    Text('You may also love', style: AppTypography.sectionTitle(size: 17)),
                    const SizedBox(height: 12),
                    SizedBox(
                      height: 220,
                      child: ListView.builder(
                        scrollDirection: Axis.horizontal,
                        itemCount: _related.length,
                        itemBuilder: (context, i) {
                          final related = _related[i];
                          return SizedBox(
                            width: 140,
                            child: ProductCard(
                              product: related,
                              compact: true,
                              isWishlisted: wishlist.isWishlisted(related.id),
                              onWishlistTap: () => wishlist.toggle(related.id, product: related),
                              onTap: () => Navigator.of(context).pushReplacementNamed('/product/${related.slug}'),
                            ),
                          );
                        },
                      ),
                    ),
                  ],

                  const SizedBox(height: 32),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _InfoBadge extends StatelessWidget {
  const _InfoBadge(this.label, this.color);

  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.92),
        borderRadius: BorderRadius.circular(2),
      ),
      child: Text(
        label,
        style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.w700, letterSpacing: 1),
      ),
    );
  }
}

class _ReviewTile extends StatelessWidget {
  const _ReviewTile({required this.review});

  final Review review;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.paper,
        borderRadius: BorderRadius.circular(4),
        border: Border.all(color: AppColors.line),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                review.customerName ?? 'Verified buyer',
                style: AppTypography.bodyMedium(weight: FontWeight.w600),
              ),
              if (review.date != null)
                Text(
                  '${review.date!.day}/${review.date!.month}/${review.date!.year}',
                  style: AppTypography.bodySmall(size: 11),
                ),
            ],
          ),
          const SizedBox(height: 6),
          RatingStars(rating: review.rating.toDouble(), showCount: false, size: 14),
          const SizedBox(height: 6),
          if (review.body != null && review.body!.isNotEmpty)
            Text(review.body!, style: AppTypography.body(size: 13.5, color: AppColors.ink)),
        ],
      ),
    );
  }
}