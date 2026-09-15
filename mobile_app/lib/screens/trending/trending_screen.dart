import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../data/repositories/catalog_repository.dart';
import '../../models/product.dart';
import '../../providers/wishlist_provider.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_typography.dart';
import '../../widgets/load_state.dart';
import '../../widgets/product_card.dart';
import '../../widgets/product_grid_ratio.dart';

/// Trending tab — rave-list from the backend (bestsellers / most viewed).
class TrendingScreen extends StatefulWidget {
  const TrendingScreen({super.key});

  @override
  State<TrendingScreen> createState() => _TrendingScreenState();
}

class _TrendingScreenState extends State<TrendingScreen> {
  List<Product>? _products;
  bool _loading = true;
  bool _failed = false;

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
      final result = await CatalogRepository.collectionProducts(
        'trending',
        sort: 'relevance',
        perPage: 60,
      );
      if (mounted) {
        setState(() {
          _products = result.items;
          _loading = false;
        });
      }
    } catch (_) {
      if (mounted)
        setState(() {
          _failed = true;
          _loading = false;
        });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const LoadState.loading();
    if (_failed && _products == null) {
      return LoadState.error(
        message: 'Could not load trending items.',
        onRetry: _load,
      );
    }

    final products = _products ?? [];
    final wishlist = context.watch<WishlistProvider>();

    return Scaffold(
      appBar: AppBar(title: const Text('Trending')),
      body: RefreshIndicator(
        onRefresh: _load,
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 6),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Trending this week',
                      style: AppTypography.scriptAccent(size: 30),
                    ),
                    Text(
                      'Most loved',
                      style: AppTypography.sectionTitle(size: 20),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'The pieces everyone is adding to their wishlists.',
                      style: AppTypography.bodySmall(color: AppColors.muted),
                    ),
                    const SizedBox(height: 16),
                  ],
                ),
              ),
            ),
            if (products.isEmpty)
              const SliverFillRemaining(
                hasScrollBody: false,
                child: LoadState.empty(message: 'Nothing trending right now.'),
              )
            else
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                sliver: SliverGrid(
                  gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 2,
                    mainAxisSpacing: 14,
                    crossAxisSpacing: 10,
                    childAspectRatio: productGridRatio(
                      MediaQuery.sizeOf(context).width,
                      compact: true,
                    ),
                  ),
                  delegate: SliverChildBuilderDelegate((context, i) {
                    final product = products[i];
                    return ProductCard(
                      product: product,
                      compact: true,
                      isWishlisted: wishlist.isWishlisted(product.id),
                      onWishlistTap: () =>
                          wishlist.toggle(product.id, product: product),
                      onTap: () => Navigator.of(
                        context,
                      ).pushNamed('/product/${product.slug}'),
                    );
                  }, childCount: products.length),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
