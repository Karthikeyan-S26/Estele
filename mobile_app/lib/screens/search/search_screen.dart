import 'dart:async';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../config/app_config.dart';
import '../../data/repositories/catalog_repository.dart';
import '../../models/product.dart';
import '../../providers/wishlist_provider.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_typography.dart';
import '../../widgets/load_state.dart';
import '../catalog/product_grid_screen.dart';

/// Search entry — a debounced pull-to-search over `/search` plus a slim grid
/// of results. Tapping a product goes to detail.
class SearchScreen extends StatefulWidget {
  const SearchScreen({super.key});

  @override
  State<SearchScreen> createState() => _SearchScreenState();
}

class _SearchScreenState extends State<SearchScreen> {
  final _controller = TextEditingController();
  Timer? _debounce;
  List<Product> _suggestions = [];
  bool _searching = false;
  String? _error;

  bool get _queryEmpty => _controller.text.trim().isEmpty;

  @override
  void initState() {
    super.initState();
    _controller.addListener(_onTextChanged);
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _controller.dispose();
    super.dispose();
  }

  void _onTextChanged() {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), () {
      if (_controller.text.trim().isEmpty) {
        setState(() {
          _suggestions = [];
          _searching = false;
        });
        return;
      }
      _suggest();
    });
  }

  Future<void> _suggest() async {
    setState(() {
      _searching = true;
      _error = null;
    });
    try {
      final products = await CatalogRepository.suggest(_controller.text.trim());
      if (mounted) {
        setState(() {
          _suggestions = products;
          _searching = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _searching = false;
          _error = e.toString();
        });
      }
    }
  }

  /// Opens the full filtered result grid once the keyword is entered.
  void _openResults(String query) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => ProductGridScreen(
          title: 'Results for "$query"',
          autoLoad: true,
          loader:
              ({required String sort, String? minPrice, String? maxPrice, required bool inStock, required int page, required int perPage}) {
            return CatalogRepository.search(
              query,
              sort: sort,
              minPrice: minPrice,
              maxPrice: maxPrice,
              inStock: inStock,
              page: page,
              perPage: perPage,
            );
          },
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final wishlist = context.watch<WishlistProvider>();

    return Scaffold(
      appBar: AppBar(
        titleSpacing: 0,
        title: TextField(
          controller: _controller,
          autofocus: true,
          textInputAction: TextInputAction.search,
          decoration: const InputDecoration(
            hintText: 'Search necklaces, kundan, earrings…',
            border: InputBorder.none,
            filled: false,
          ),
          onSubmitted: _queryEmpty ? null : (q) => _openResults(q),
        ),
        actions: [
          if (!_queryEmpty)
            IconButton(
              onPressed: () {
                _controller.clear();
                setState(() {
                  _suggestions = [];
                });
              },
              icon: const Icon(Icons.clear_rounded),
            ),
        ],
      ),
      body: _queryEmpty
          ? _IdleView(onSearch: (q) => _openResults(q))
          : _searching
              ? const LoadState.loading()
              : _error != null && _suggestions.isEmpty
                  ? LoadState.empty(message: 'No results for that search.')
                  : _suggestions.isEmpty
                      ? const LoadState.loading()
                      : ListView(
                          padding: const EdgeInsets.all(16),
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text('Suggestions', style: AppTypography.label(letterSpacing: 1.2)),
                                Text('${_suggestions.length} found', style: AppTypography.bodySmall(size: 12)),
                              ],
                            ),
                            const SizedBox(height: 8),
                            for (final product in _suggestions)
                              ListTile(
                                contentPadding: EdgeInsets.zero,
                                leading: SizedBox(
                                  width: 44,
                                  height: 56,
                                  child: _SuggestionImage(url: product.imageUrl),
                                ),
                                title: Text(product.title, maxLines: 1, overflow: TextOverflow.ellipsis),
                                subtitle: Text(product.sku, style: AppTypography.bodySmall(size: 11)),
                                trailing: IconButton(
                                  icon: Icon(
                                    wishlist.isWishlisted(product.id)
                                        ? Icons.favorite_rounded
                                        : Icons.favorite_outline_rounded,
                                    color: wishlist.isWishlisted(product.id) ? AppColors.accent : AppColors.muted,
                                    size: 20,
                                  ),
                                  onPressed: () => wishlist.toggle(product.id, product: product),
                                ),
                                onTap: () =>
                                    Navigator.of(context).pushNamed('/product/${product.slug}'),
                              ),
                          ],
                        ),
    );
  }
}

class _IdleView extends StatelessWidget {
  const _IdleView({required this.onSearch});

  final ValueChanged<String> onSearch;

  @override
  Widget build(BuildContext context) {
    const popular = ['Kundan', 'Gold necklace', 'Jhumka', 'Earrings', 'Bridal', 'Ring'];

    return Padding(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Popular searches', style: AppTypography.label(letterSpacing: 1.2)),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: popular
                .map((q) => ActionChip(
                      label: Text(q),
                      onPressed: () => onSearch(q),
                    ))
                .toList(),
          ),
        ],
      ),
    );
  }
}

class _SuggestionImage extends StatelessWidget {
  const _SuggestionImage({required this.url});

  final String? url;

  @override
  Widget build(BuildContext context) {
    final urlValue = AppConfig.resolveMediaUrl(url);
    if (urlValue == null || urlValue.isEmpty) {
      return Container(
        color: AppColors.warmBeige,
        child: const Icon(Icons.image_outlined, color: AppColors.lineStrong, size: 20),
      );
    }
    return Image.network(
      urlValue,
      fit: BoxFit.cover,
      errorBuilder: (_, __, ___) => Container(
        color: AppColors.warmBeige,
        child: const Icon(Icons.image_outlined, color: AppColors.lineStrong, size: 20),
      ),
    );
  }
}