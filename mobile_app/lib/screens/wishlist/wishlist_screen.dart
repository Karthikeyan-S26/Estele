import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../providers/wishlist_provider.dart';
import '../../widgets/load_state.dart';
import '../../widgets/product_card.dart';
import '../../widgets/product_grid_ratio.dart';

/// Standalone wishlist page (pushed route `/wishlist`) — Scaffold + the shared
/// [WishlistView] body.
class WishlistScreen extends StatelessWidget {
  const WishlistScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Wishlist')),
      body: const WishlistView(),
    );
  }
}

/// Wishlist content — used both as a bottom-nav tab (inside the shell, which
/// already provides the app bar) and inside [WishlistScreen].
class WishlistView extends StatefulWidget {
  const WishlistView({super.key});

  @override
  State<WishlistView> createState() => _WishlistViewState();
}

class _WishlistViewState extends State<WishlistView> {
  bool _hydrating = false;

  @override
  void initState() {
    super.initState();
    // Defer past the current build — `wishlist.hydrate()` calls
    // `notifyListeners()`, which must never fire inside the build phase
    // (it used to make the Wishlist tab appear dead on first open).
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) _hydrate();
    });
  }

  Future<void> _hydrate() async {
    final wishlist = context.read<WishlistProvider>();
    if (wishlist.hydrated && wishlist.products.length == wishlist.ids.length) {
      return;
    }
    setState(() => _hydrating = true);
    await wishlist.hydrate();
    if (mounted) setState(() => _hydrating = false);
  }

  @override
  Widget build(BuildContext context) {
    final wishlist = context.watch<WishlistProvider>();

    // Keep the clear action reachable inside the tab without its own app bar:
    // a slim header row above the grid.
    return Column(
      children: [
        if (wishlist.count > 0)
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 8, 0),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  '${wishlist.count} ${wishlist.count == 1 ? 'item' : 'items'} saved',
                  style: Theme.of(context).textTheme.bodySmall,
                ),
                TextButton.icon(
                  onPressed: () => wishlist.clear(),
                  icon: const Icon(Icons.delete_sweep_outlined, size: 17),
                  label: const Text('Clear'),
                ),
              ],
            ),
          ),
        Expanded(
          child: wishlist.ids.isEmpty
              ? const LoadState.empty(
                  message:
                      'Nothing saved yet. Tap the heart on any piece to keep it here.',
                )
              : _hydrating && wishlist.products.isEmpty
              ? const LoadState.loading()
              : RefreshIndicator(
                  onRefresh: _hydrate,
                  child: LayoutBuilder(
                    builder: (context, constraints) => GridView.builder(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.all(16),
                      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                        crossAxisCount: 2,
                        mainAxisSpacing: 14,
                        crossAxisSpacing: 10,
                        childAspectRatio: productGridRatio(
                          constraints.maxWidth,
                          compact: true,
                        ),
                      ),
                      itemCount: wishlist.products.length,
                      itemBuilder: (context, i) {
                        final product = wishlist.products[i];
                        return ProductCard(
                          product: product,
                          compact: true,
                          isWishlisted: true,
                          onWishlistTap: () =>
                              wishlist.removeProduct(product.id),
                          onTap: () async {
                            await Navigator.of(
                              context,
                            ).pushNamed('/product/${product.slug}');
                            _hydrate();
                          },
                        );
                      },
                    ),
                  ),
                ),
        ),
      ],
    );
  }
}
