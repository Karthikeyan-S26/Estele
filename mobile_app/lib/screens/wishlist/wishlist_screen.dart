import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../providers/wishlist_provider.dart';
import '../../widgets/load_state.dart';
import '../../widgets/product_card.dart';

class WishlistScreen extends StatefulWidget {
  const WishlistScreen({super.key});

  @override
  State<WishlistScreen> createState() => _WishlistScreenState();
}

class _WishlistScreenState extends State<WishlistScreen> {
  bool _hydrating = false;

  @override
  void initState() {
    super.initState();
    _hydrate();
  }

  Future<void> _hydrate() async {
    final wishlist = context.read<WishlistProvider>();
    if (wishlist.hydrated && wishlist.products.isNotEmpty) return;
    setState(() => _hydrating = true);
    await wishlist.hydrate();
    if (mounted) setState(() => _hydrating = false);
  }

  @override
  Widget build(BuildContext context) {
    final wishlist = context.watch<WishlistProvider>();

    return Scaffold(
      appBar: AppBar(
        title: const Text('Wishlist'),
        actions: [
          if (wishlist.count > 0)
            IconButton(
              tooltip: 'Clear wishlist',
              onPressed: () => wishlist.clear(),
              icon: const Icon(Icons.delete_sweep_outlined),
            ),
        ],
      ),
      body: wishlist.ids.isEmpty
          ? LoadState.empty(message: 'Nothing saved yet. Tap the heart on any piece to keep it here.')
          : _hydrating && wishlist.products.isEmpty
              ? const LoadState.loading()
              : RefreshIndicator(
                  onRefresh: _hydrate,
                  child: GridView.builder(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: 2,
                      mainAxisSpacing: 14,
                      crossAxisSpacing: 10,
                      childAspectRatio: 0.62,
                    ),
                    itemCount: wishlist.products.length,
                    itemBuilder: (context, i) {
                      final product = wishlist.products[i];
                      return ProductCard(
                        product: product,
                        compact: true,
                        isWishlisted: true,
                        onWishlistTap: () => wishlist.removeProduct(product.id),
                        onTap: () async {
                          await Navigator.of(context).pushNamed('/product/${product.slug}');
                          _hydrate();
                        },
                      );
                    },
                  ),
                ),
    );
  }
}