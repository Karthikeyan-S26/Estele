import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../data/api_client.dart';
import '../../data/repositories/catalog_repository.dart';
import '../../models/product.dart';
import '../../models/review.dart';
import '../../providers/auth_provider.dart';
import '../../providers/cart_provider.dart';
import '../../providers/checkout_intent.dart';
import '../../providers/wishlist_provider.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_typography.dart';
import '../../widgets/app_image.dart';
import '../../widgets/load_state.dart';
import '../../widgets/newsletter_popup.dart';
import '../../widgets/price_text.dart';
import '../../widgets/product_card.dart';
import '../../widgets/quantity_stepper.dart';
import '../../widgets/rating_stars.dart';
import '../auth/login_screen.dart';

class ProductDetailScreen extends StatefulWidget {
  const ProductDetailScreen({
    super.key,
    required this.slug,
    @visibleForTesting this.previewProduct,
  });

  /// Test seam: render a fully-formed PDP straight from an in-memory product
  /// without touching the catalog API.
  @visibleForTesting
  ProductDetailScreen.preview({super.key, required Product product})
      : slug = product.slug,
        previewProduct = product;

  final String slug;

  /// When set (test-only), the screen seeds directly from this product and
  /// skips the network load.
  @visibleForTesting
  final Product? previewProduct;

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
    final preview = widget.previewProduct;
    if (preview != null) {
      _product = preview;
      _initVariants(preview);
      _loading = false;
      return;
    }
    _load();
  }

  Future<void> _openWriteReview() async {
    final auth = context.read<AuthProvider>();
    if (!auth.isAuthenticated) {
      await Navigator.of(
        context,
      ).push(MaterialPageRoute(builder: (_) => const LoginScreen()));
      return;
    }
    final submitted = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      builder: (_) => _WriteReviewSheet(slug: widget.slug),
    );
    if (submitted != true || !mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Thanks for your review! It will appear once approved.'),
      ),
    );
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
        _reviews = result.page.reviews.map((e) => Review.fromJson(e)).toList();
        _initVariants(result.page.product);
        _loading = false;
      });
      // One-shot "Get the Glow" prompt (prefs-guarded inside).
      WidgetsBinding.instance.addPostFrameCallback((_) {
        Future.delayed(const Duration(seconds: 2), () {
          if (!mounted) return;
          NewsletterPopup.maybeShow(context);
        });
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
          ? product.variants
                .firstWhere(
                  (v) => v.inStock,
                  orElse: () => product.variants.first,
                )
                .sku
          : product.variants.first.sku;
      _selectedSizeIndex = _sizes.indexOf(
        product.variants.first.attributes.values.firstOrNull ?? '',
      );
    }
  }

  /// Adds the selected variant + quantity to the cart. Returns null on
  /// success, or a user-facing error message.
  Future<String?> _addItem(CartProvider cart) async {
    final product = _product!;
    if (product.hasVariants) {
      final variant = product.variants
          .where((v) => v.sku == _selectedSku)
          .firstOrNull;
      return cart.addItem(
        productId: product.id,
        productSlug: product.slug,
        variantId: variant?.id,
        quantity: _quantity,
      );
    }
    return cart.addItem(
      productId: product.id,
      productSlug: product.slug,
      quantity: _quantity,
    );
  }

  Future<void> _addToCart(CartProvider cart) async {
    final product = _product!;
    if (product.inStock == false) return;

    setState(() => _addingToCart = true);
    final error = await _addItem(cart);
    if (!mounted) return;
    setState(() {
      _addingToCart = false;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(error ?? 'Added to your bag')),
      );
    });
  }

  /// Website "Buy It Now" parity: drop the selected quantity + variant into
  /// the (same) cart, then go straight to checkout. Authenticated users land
  /// directly in checkout; guests fold through login → OTP first — the
  /// pending purchase (guest cart) is merged into their account server-side
  /// at sign-in, and [CheckoutIntent] carries them back into checkout right
  /// after authentication instead of dropping them at Home.
  Future<void> _buyNow(CartProvider cart) async {
    final product = _product!;
    if (product.inStock == false) return;

    setState(() => _addingToCart = true);
    final error = await _addItem(cart);
    if (!mounted) return;
    if (error != null) {
      setState(() {
        _addingToCart = false;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(error)),
        );
      });
      return;
    }
    setState(() => _addingToCart = false);
    final authed = context.read<AuthProvider>().isAuthenticated;
    if (authed) {
      await Navigator.of(context).pushNamed('/checkout');
    } else {
      CheckoutIntent.arm();
      await Navigator.of(context).pushNamed('/login');
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Scaffold(body: LoadState.loading());
    }
    if (_failed || _product == null) {
      return Scaffold(
        appBar: AppBar(),
        body: LoadState.error(
          message: 'Could not load this piece.',
          onRetry: _load,
        ),
      );
    }

    final product = _product!;
    final wishlist = context.watch<WishlistProvider>();
    final cart = context.watch<CartProvider>();

    return Scaffold(
      // Website mobile parity: the buy bar pins to the viewport bottom
      // while the page scrolls. Scaffold docks it — no scroll hacks, no
      // content overlap, SafeArea + keyboard handled by the framework.
      // PDP is a pushed route, so there is no bottom navigation to clash.
      bottomNavigationBar: _BuyBar(
        inStock: product.inStock != false,
        wishlisted: wishlist.isWishlisted(product.id),
        onWishlistTap: () => wishlist.toggle(product.id, product: product),
        quantity: _quantity,
        onQuantityChanged: (q) => setState(() => _quantity = q),
        adding: _addingToCart,
        onAdd: () => _addToCart(cart),
        onBuyNow: () => _buyNow(cart),
      ),
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            pinned: true,
            expandedHeight: 340,
            backgroundColor: AppColors.ivory,
            foregroundColor: AppColors.heading,
            actions: [
              // NOTE: the wishlist heart lives ONLY in the sticky buy bar
              // below (website parity — no duplicate toggle up here).
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
                    itemCount: product.gallery.isEmpty
                        ? 1
                        : product.gallery.length,
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
                              final selected =
                                  (_pageController.page ?? 0).round() == i;
                              return Container(
                                width: selected ? 12 : 6,
                                height: 6,
                                margin: const EdgeInsets.symmetric(
                                  horizontal: 3,
                                ),
                                decoration: BoxDecoration(
                                  color: selected
                                      ? AppColors.accent
                                      : AppColors.lineStrong,
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
                    RatingStars(
                      rating: _avgRating,
                      count: _reviewCount,
                      size: 16,
                    )
                  else
                    Text(
                      'New arrival',
                      style: AppTypography.bodySmall(size: 12),
                    ),
                  const SizedBox(height: 10),
                  PriceText(
                    price: product.price,
                    compareAtPrice: product.compareAtPrice,
                    discountPercent: product.effectiveDiscountPercent > 0
                        ? product.effectiveDiscountPercent
                        : null,
                    size: 22,
                  ),
                  const SizedBox(height: 10),
                  if (product.inStock == false)
                    Text(
                      'Currently out of stock',
                      style: AppTypography.bodyMedium(color: AppColors.sale),
                    ),
                  const SizedBox(height: 4),
                  Text(
                    'SKU ${product.sku}',
                    style: AppTypography.bodySmall(size: 11),
                  ),

                  // Variant size selector
                  if (_sizes.isNotEmpty) ...[
                    const SizedBox(height: 18),
                    Text(
                      'Select size',
                      style: AppTypography.label(letterSpacing: 1.2),
                    ),
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
                                  .where(
                                    (v) =>
                                        v.attributes.values.contains(_sizes[i]),
                                  )
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
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    color: Colors.white,
                                  ),
                                )
                              : const Icon(
                                  Icons.shopping_bag_outlined,
                                  size: 18,
                                ),
                          label: Text(
                            product.inStock ? 'Add to bag' : 'Out of stock',
                          ),
                        ),
                      ),
                    ],
                  ),
                  // Website "Buy It Now" parity (outline-accent, full-width,
                  // below Add to Cart, disabled when out of stock).
                  //
                  // The label deliberately carries NO explicit color: OutlinedButton
                  // paints its label through DefaultTextStyle from `foregroundColor`
                  // / `disabledForegroundColor`, and an inline white (the
                  // AppTypography.button default) would override that and render the
                  // label invisible on a light background.
                  const SizedBox(height: 10),
                  OutlinedButton(
                    onPressed: product.inStock && !_addingToCart
                        ? () => _buyNow(cart)
                        : null,
                    style: OutlinedButton.styleFrom(
                      minimumSize: const Size.fromHeight(48),
                      side: const BorderSide(color: AppColors.accent, width: 2),
                      foregroundColor: AppColors.accent,
                      disabledForegroundColor: AppColors.soldOut,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(6),
                      ),
                    ),
                    child: Text(
                      'BUY IT NOW',
                      style: AppTypography.button(
                        size: 13,
                        color: null, // inherit the button's accent / disabled
                        letterSpacing: 0.6,
                      ),
                    ),
                  ),
                  const SizedBox(height: 6),
                  TextButton.icon(
                    onPressed: () {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(
                          content: Text(
                            'Free shipping over ₹999 · 7-day easy returns',
                          ),
                        ),
                      );
                    },
                    icon: const Icon(Icons.local_shipping_outlined, size: 16),
                    label: const Text('Free shipping over ₹999 · Easy returns'),
                  ),

                  const Divider(height: 32),

                  // Description
                  Text(
                    'About this piece',
                    style: AppTypography.sectionTitle(size: 17),
                  ),
                  const SizedBox(height: 10),
                  Text(
                    product.description ?? 'A timeless Estele creation.',
                    style: AppTypography.body(size: 14, color: AppColors.ink),
                  ),

                  // Related — website "You May Also Like", ahead of the
                  // promise strip and reviews like the site's PDP order.
                  if (_related.isNotEmpty) ...[
                    const Divider(height: 32),
                    Text(
                      'You May Also Like',
                      style: AppTypography.sectionTitle(size: 17),
                    ),
                    const SizedBox(height: 12),
                    SizedBox(
                      // Related-rail cards carry the CTA row: image (4:5 at
                      // 140px wide) + body with title, price and CTAs.
                      height: 280,
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
                              onWishlistTap: () =>
                                  wishlist.toggle(related.id, product: related),
                              onTap: () =>
                                  Navigator.of(context).pushReplacementNamed(
                                    '/product/${related.slug}',
                                  ),
                            ),
                          );
                        },
                      ),
                    ),
                  ],

                  // Our Promise to You — website PDP trust strip (static
                  // content, hardcoded on the site too).
                  const Divider(height: 32),
                  Text(
                    'Our Promise to You',
                    style: AppTypography.sectionTitle(size: 17),
                  ),
                  const SizedBox(height: 12),
                  const _PromiseStrip(),

                  // Reviews — hidden entirely until a product has at least
                  // one review, exactly like the website (an empty "be the
                  // first" block reads as a negative signal there).
                  if (_reviewCount > 0 || _reviews.isNotEmpty) ...[
                    const Divider(height: 32),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          'Reviews',
                          style: AppTypography.sectionTitle(size: 17),
                        ),
                        TextButton.icon(
                          onPressed: _openWriteReview,
                          icon: const Icon(Icons.rate_review_outlined, size: 18),
                          label: const Text('Write a review'),
                        ),
                      ],
                    ),
                    if (_reviewCount > 0)
                      Padding(
                        padding: const EdgeInsets.only(top: 4),
                        child: Text(
                          '$_reviewCount review${_reviewCount == 1 ? '' : 's'}',
                          style: AppTypography.bodySmall(),
                        ),
                      ),
                    const SizedBox(height: 12),
                    ..._reviews.take(3).map((r) => _ReviewTile(review: r)),
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

/// Website PDP "Our Promise to You" trust strip — the site hardcodes these
/// five cards in Blade, so the identical values live here (no endpoint).
class _PromiseStrip extends StatelessWidget {
  const _PromiseStrip();

  static const _promises = [
    (
      title: '24K Gold Plated',
      subtitle: 'Precious long-lasting shine',
      icon: Icons.workspace_premium_outlined,
    ),
    (
      title: 'Skin Friendly',
      subtitle: 'Nickel & lead free formula',
      icon: Icons.health_and_safety_outlined,
    ),
    (
      title: '35+ Years Legacy',
      subtitle: 'Trusted by 5M+ happy women',
      icon: Icons.military_tech_outlined,
    ),
    (
      title: '7-Day Easy Returns',
      subtitle: '100% exchange guarantee',
      icon: Icons.autorenew_rounded,
    ),
    (
      title: 'Free Shipping',
      subtitle: 'Express Pan-India delivery',
      icon: Icons.local_shipping_outlined,
    ),
  ];

  @override
  Widget build(BuildContext context) {
    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      mainAxisSpacing: 10,
      crossAxisSpacing: 10,
      childAspectRatio: 1.35,
      children: [
        for (final p in _promises)
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 14),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: AppColors.line),
            ),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(p.icon, size: 24, color: AppColors.heading),
                const SizedBox(height: 8),
                Text(
                  p.title,
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontFamily: 'Cinzel',
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: AppColors.heading,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  p.subtitle,
                  textAlign: TextAlign.center,
                  style: AppTypography.bodySmall(
                    size: 11,
                    color: AppColors.muted,
                  ),
                ),
              ],
            ),
          ),
      ],
    );
  }
}

/// Website mobile parity sticky buy bar (`.buybar`): wishlist heart +
/// quantity stepper + Buy Now (outline) + Add to Bag (filled), all 49px.
/// Reuses the screen's existing state and cart/Buy Now flows verbatim —
/// this widget only docks them to the viewport bottom.
class _BuyBar extends StatelessWidget {
  const _BuyBar({
    required this.inStock,
    required this.wishlisted,
    required this.onWishlistTap,
    required this.quantity,
    required this.onQuantityChanged,
    required this.adding,
    required this.onAdd,
    required this.onBuyNow,
  });

  final bool inStock;
  final bool wishlisted;
  final VoidCallback onWishlistTap;
  final int quantity;
  final ValueChanged<int> onQuantityChanged;
  final bool adding;
  final VoidCallback onAdd;
  final VoidCallback onBuyNow;

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      top: false,
      child: Container(
        decoration: const BoxDecoration(
          color: AppColors.paper,
          border: Border(top: BorderSide(color: AppColors.line)),
        ),
        padding: const EdgeInsets.fromLTRB(12, 10, 12, 10),
        child: inStock
            ? Row(
                children: [
                  SizedBox(
                    width: 44,
                    height: 49,
                    child: IconButton(
                      onPressed: onWishlistTap,
                      padding: EdgeInsets.zero,
                      icon: Icon(
                        wishlisted
                            ? Icons.favorite_rounded
                            : Icons.favorite_outline_rounded,
                        size: 28,
                        color: wishlisted
                            ? AppColors.accent
                            : AppColors.heading,
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  QuantityStepper(
                    quantity: quantity,
                    onChanged: onQuantityChanged,
                    max: 10,
                    size: 30,
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: OutlinedButton(
                      onPressed: adding ? null : onBuyNow,
                      style: OutlinedButton.styleFrom(
                        minimumSize: const Size.fromHeight(49),
                        side: const BorderSide(
                          color: AppColors.accent,
                          width: 2,
                        ),
                        foregroundColor: AppColors.accent,
                        disabledForegroundColor: AppColors.soldOut,
                        padding: EdgeInsets.zero,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(6),
                        ),
                      ),
                      child: Text(
                        'BUY IT NOW',
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
                  const SizedBox(width: 8),
                  Expanded(
                    child: FilledButton(
                      onPressed: adding ? null : onAdd,
                      style: FilledButton.styleFrom(
                        backgroundColor: AppColors.accentDark,
                        disabledBackgroundColor:
                            AppColors.accentDark.withValues(alpha: 0.6),
                        minimumSize: const Size.fromHeight(49),
                        padding: EdgeInsets.zero,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(6),
                        ),
                        elevation: 1,
                      ),
                      child: adding
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : Text(
                              'ADD TO BAG',
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: AppTypography.button(
                                size: 12,
                                letterSpacing: 0.3,
                              ),
                            ),
                    ),
                  ),
                ],
              )
            : SizedBox(
                width: double.infinity,
                child: FilledButton(
                  onPressed: null,
                  style: FilledButton.styleFrom(
                    minimumSize: const Size.fromHeight(49),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(6),
                    ),
                  ),
                  child: Text(
                    'Out of Stock',
                    style: AppTypography.button(size: 13, letterSpacing: 0.6),
                  ),
                ),
              ),
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
        style: const TextStyle(
          color: Colors.white,
          fontSize: 10,
          fontWeight: FontWeight.w700,
          letterSpacing: 1,
        ),
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
          RatingStars(
            rating: review.rating.toDouble(),
            showCount: false,
            size: 14,
          ),
          const SizedBox(height: 6),
          if (review.body != null && review.body!.isNotEmpty)
            Text(
              review.body!,
              style: AppTypography.body(size: 13.5, color: AppColors.ink),
            ),
        ],
      ),
    );
  }
}

/// Bottom-sheet for writing a product review (rating + optional title + body).
class _WriteReviewSheet extends StatefulWidget {
  const _WriteReviewSheet({required this.slug});
  final String slug;
  @override
  State<_WriteReviewSheet> createState() => _WriteReviewSheetState();
}

class _WriteReviewSheetState extends State<_WriteReviewSheet> {
  final _formKey = GlobalKey<FormState>();
  final _title = TextEditingController();
  final _body = TextEditingController();
  int _rating = 5;
  bool _submitting = false;
  String? _error;

  @override
  void dispose() {
    _title.dispose();
    _body.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final bodyText = _body.text.trim();
    if (bodyText.isEmpty) {
      setState(() => _error = 'Please write a few words about the piece.');
      return;
    }
    setState(() {
      _submitting = true;
      _error = null;
    });
    try {
      await CatalogRepository.storeReview(
        slug: widget.slug,
        rating: _rating,
        title: _title.text.trim(),
        body: bodyText,
      );
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() {
        _submitting = false;
        _error = e.message;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _submitting = false;
        _error = 'Could not submit your review.';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        left: 20,
        right: 20,
        top: 20,
        bottom: MediaQuery.of(context).viewInsets.bottom + 20,
      ),
      child: Form(
        key: _formKey,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Write a review', style: AppTypography.sectionTitle(size: 17)),
            const SizedBox(height: 14),
            Text('Your rating', style: AppTypography.label(letterSpacing: 0.8)),
            const SizedBox(height: 6),
            Row(
              children: List.generate(5, (i) {
                final star = i + 1;
                return IconButton(
                  onPressed: _submitting
                      ? null
                      : () => setState(() => _rating = star),
                  icon: Icon(
                    star <= _rating
                        ? Icons.star_rounded
                        : Icons.star_border_rounded,
                    color: AppColors.gold,
                    size: 30,
                  ),
                );
              }),
            ),
            const SizedBox(height: 8),
            TextField(
              controller: _title,
              enabled: !_submitting,
              maxLength: 150,
              decoration: const InputDecoration(
                labelText: 'Title (optional)',
                border: OutlineInputBorder(),
                counterText: '',
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _body,
              enabled: !_submitting,
              maxLines: 4,
              maxLength: 3000,
              decoration: const InputDecoration(
                labelText: 'Your review',
                border: OutlineInputBorder(),
                counterText: '',
              ),
            ),
            if (_error != null) ...[
              const SizedBox(height: 8),
              Text(
                _error!,
                style: AppTypography.bodySmall(
                  size: 12.5,
                  color: AppColors.error,
                ),
              ),
            ],
            const SizedBox(height: 16),
            FilledButton(
              onPressed: _submitting ? null : _submit,
              style: FilledButton.styleFrom(
                minimumSize: const Size.fromHeight(44),
                backgroundColor: AppColors.deepWine,
              ),
              child: _submitting
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    )
                  : const Text('Submit review'),
            ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
  }
}
