import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../models/product.dart';
import '../../providers/cart_provider.dart';
import '../../providers/wishlist_provider.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_typography.dart';
import '../../widgets/load_state.dart';
import '../../widgets/product_card.dart';
import '../../widgets/product_grid_ratio.dart';

/// Signature for a page-loader used by [ProductGridScreen].
typedef ProductPageLoader =
    Future<({List<Product> items, Map<String, dynamic> meta})> Function({
      required String sort,
      String? minPrice,
      String? maxPrice,
      required bool inStock,
      required int page,
      required int perPage,
    });

const _sortOptions = <String, String>{
  'relevance': 'Relevance',
  'newest': 'Newest first',
  'price_low_to_high': 'Price: Low to High',
  'price_high_to_low': 'Price: High to Low',
  'rating': 'Rating',
};

/// A full-screen paginated product grid with pull-to-refresh, sort sheet,
/// price/in-stock filters, and infinite scroll — used for categories,
/// collections, trending and search results.
class ProductGridScreen extends StatefulWidget {
  const ProductGridScreen({
    super.key,
    required this.title,
    required this.loader,
    this.autoLoad = true,
  });

  final String title;
  final ProductPageLoader loader;
  final bool autoLoad;

  @override
  State<ProductGridScreen> createState() => _ProductGridScreenState();
}

class _ProductGridScreenState extends State<ProductGridScreen> {
  final _products = <Product>[];
  String _sort = 'relevance';
  String? _minPrice;
  String? _maxPrice;
  bool _inStockOnly = false;
  bool _initialLoading = false;
  bool _loadingMore = false;
  bool _hasMore = false;
  int _page = 1;
  bool _failed = false;
  String? _error;

  final _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    if (widget.autoLoad) _reload();
    _scrollController.addListener(() {
      if (_scrollController.position.pixels >=
          _scrollController.position.maxScrollExtent - 400) {
        _loadMore();
      }
    });
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _reload() async {
    setState(() {
      _initialLoading = true;
      _failed = false;
      _error = null;
      _page = 1;
    });
    try {
      final result = await widget.loader(
        sort: _sort,
        minPrice: _minPrice,
        maxPrice: _maxPrice,
        inStock: _inStockOnly,
        page: 1,
        perPage: 24,
      );
      if (!mounted) return;
      setState(() {
        _products
          ..clear()
          ..addAll(result.items);
        _page = 1;
        _hasMore =
            ((result.meta['total'] as num?)?.toInt() ?? 0) > _products.length;
        _initialLoading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _failed = true;
        _error = e.toString();
        _initialLoading = false;
      });
    }
  }

  Future<void> _loadMore() async {
    if (_loadingMore || !_hasMore || _initialLoading || _failed) return;
    setState(() => _loadingMore = true);
    try {
      final result = await widget.loader(
        sort: _sort,
        minPrice: _minPrice,
        maxPrice: _maxPrice,
        inStock: _inStockOnly,
        page: _page + 1,
        perPage: 24,
      );
      if (!mounted) return;
      setState(() {
        _products.addAll(result.items);
        _page += 1;
        _hasMore =
            ((result.meta['total'] as num?)?.toInt() ?? 0) > _products.length;
        _loadingMore = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _loadingMore = false);
    }
  }

  void _openFilters() {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder: (_) => _FilterSheet(
        initialSort: _sort,
        minPrice: _minPrice,
        maxPrice: _maxPrice,
        inStock: _inStockOnly,
        onApply: (sort, minP, maxP, inStock) {
          setState(() {
            _sort = sort;
            _minPrice = minP;
            _maxPrice = maxP;
            _inStockOnly = inStock;
          });
          _reload();
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final wishlist = context.watch<WishlistProvider>();

    return Scaffold(
      appBar: AppBar(title: Text(widget.title)),
      body: _initialLoading && _products.isEmpty
          ? const LoadState.loading()
          : _failed && _products.isEmpty
          ? LoadState.error(message: _error ?? '', onRetry: _reload)
          : Column(
              children: [
                // Sort / filter bar
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 8, 8, 8),
                  child: Row(
                    children: [
                      if (_products.isNotEmpty) ...[
                        Text(
                          '${_products.length} items',
                          style: AppTypography.bodySmall(
                            color: AppColors.muted,
                          ),
                        ),
                      ],
                      const Spacer(),
                      TextButton.icon(
                        onPressed: _openFilters,
                        icon: const Icon(Icons.tune_rounded, size: 18),
                        label: Text(_sortOptions[_sort] ?? 'Sort'),
                      ),
                    ],
                  ),
                ),
                Expanded(
                  child: _products.isEmpty
                      ? const LoadState.empty(
                          message: 'No products match your filters.',
                        )
                      : RefreshIndicator(
                          onRefresh: _reload,
                          child: LayoutBuilder(
                            builder: (context, constraints) => GridView.builder(
                              controller: _scrollController,
                              physics: const AlwaysScrollableScrollPhysics(),
                              padding: const EdgeInsets.fromLTRB(12, 4, 12, 24),
                              gridDelegate:
                                  SliverGridDelegateWithFixedCrossAxisCount(
                                    crossAxisCount: 2,
                                    mainAxisSpacing: 14,
                                    crossAxisSpacing: 10,
                                    childAspectRatio: productGridRatio(
                                      constraints.maxWidth,
                                      horizontalPadding: 12,
                                      crossAxisSpacing: 10,
                                    ),
                                  ),
                              itemCount: _products.length + (_hasMore ? 1 : 0),
                              itemBuilder: (context, i) {
                                if (i >= _products.length) {
                                  return const Center(
                                    child: Padding(
                                      padding: EdgeInsets.all(12),
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2,
                                      ),
                                    ),
                                  );
                                }
                                final product = _products[i];
                                final cart = context.read<CartProvider>();
                                return ProductCard(
                                  product: product,
                                  isWishlisted: wishlist.isWishlisted(
                                    product.id,
                                  ),
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
                                                content: Text(
                                                  error ?? 'Added to bag',
                                                ),
                                                duration: const Duration(
                                                  seconds: 2,
                                                ),
                                              ),
                                            );
                                        }
                                      : null,
                                );
                              },
                            ),
                          ),
                        ),
                ),
              ],
            ),
    );
  }
}

class _FilterSheet extends StatefulWidget {
  const _FilterSheet({
    required this.initialSort,
    required this.minPrice,
    required this.maxPrice,
    required this.inStock,
    required this.onApply,
  });

  final String initialSort;
  final String? minPrice;
  final String? maxPrice;
  final bool inStock;
  final void Function(String sort, String? min, String? max, bool inStock)
  onApply;

  @override
  State<_FilterSheet> createState() => _FilterSheetState();
}

class _FilterSheetState extends State<_FilterSheet> {
  late String _sort = widget.initialSort;
  late bool _inStock = widget.inStock;
  final _min = TextEditingController(text: '');
  final _max = TextEditingController(text: '');

  @override
  void initState() {
    super.initState();
    _min.text = widget.minPrice ?? '';
    _max.text = widget.maxPrice ?? '';
  }

  @override
  void dispose() {
    _min.dispose();
    _max.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Sort & filter', style: AppTypography.sectionTitle(size: 17)),
            const SizedBox(height: 14),
            // Sort options
            for (final entry in _sortOptions.entries)
              RadioListTile<String>(
                dense: true,
                contentPadding: EdgeInsets.zero,
                title: Text(entry.value),
                value: entry.key,
                groupValue: _sort,
                onChanged: (v) => setState(() => _sort = v!),
              ),
            const SizedBox(height: 8),
            Text(
              'Price range',
              style: AppTypography.label(color: AppColors.muted),
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _min,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(
                      labelText: 'Min ₹',
                      isDense: true,
                    ),
                  ),
                ),
                const Padding(
                  padding: EdgeInsets.symmetric(horizontal: 8),
                  child: Text('–'),
                ),
                Expanded(
                  child: TextField(
                    controller: _max,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(
                      labelText: 'Max ₹',
                      isDense: true,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            SwitchListTile(
              dense: true,
              contentPadding: EdgeInsets.zero,
              title: const Text('In stock only'),
              value: _inStock,
              onChanged: (v) => setState(() => _inStock = v),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: () => Navigator.of(context).pop(),
                    child: const Text('Cancel'),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: FilledButton(
                    onPressed: () {
                      widget.onApply(
                        _sort,
                        _min.text.trim(),
                        _max.text.trim(),
                        _inStock,
                      );
                      Navigator.of(context).pop();
                    },
                    child: const Text('Apply'),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
