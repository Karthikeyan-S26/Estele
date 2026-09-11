import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../config/app_config.dart';
import '../../models/cart.dart';
import '../../providers/cart_provider.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_typography.dart';
import '../../utils/formatters.dart';
import '../../widgets/app_image.dart';
import '../../widgets/load_state.dart';
import '../../widgets/price_text.dart';
import '../../widgets/quantity_stepper.dart';

class CartScreen extends StatefulWidget {
  const CartScreen({super.key});

  @override
  State<CartScreen> createState() => _CartScreenState();
}

class _CartScreenState extends State<CartScreen> {
  final _couponCtrl = TextEditingController();
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _couponCtrl.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final provider = context.read<CartProvider>();
      provider.load();
    });
  }

  Future<void> _applyCoupon() async {
    final provider = context.read<CartProvider>();
    setState(() => _busy = true);
    final err = await provider.applyCoupon(_couponCtrl.text);
    if (!mounted) return;
    setState(() => _busy = false);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(err ?? 'Coupon applied')),
    );
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<CartProvider>();

    return Scaffold(
      appBar: AppBar(title: const Text('Your bag')),
      body: provider.initialLoading && provider.cart.items.isEmpty
          ? const LoadState.loading()
          : provider.cart.isEmpty
              ? LoadState.empty(
                  message: 'Your bag is empty.\nAdd something gorgeous to begin.',
                )
              : _buildCart(context, provider),
    );
  }

  Widget _buildCart(BuildContext context, CartProvider provider) {
    final cart = provider.cart;

    return Column(
      children: [
        Expanded(
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              // Free-shipping progress
              _ShippingProgress(cart: cart),
              const SizedBox(height: 16),

              // Items
              for (final item in cart.items)
                _CartItemTile(
                  item: item,
                  onQty: (q) => provider.updateItem(cartItemId: item.id, quantity: q),
                  onRemove: () => provider.removeItem(item.id),
                ),

              // Coupon
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppColors.paper,
                  borderRadius: BorderRadius.circular(4),
                  border: Border.all(color: AppColors.line),
                ),
                child: Row(
                  children: [
                    if (cart.couponCode != null) ...[
                      const Icon(Icons.local_offer_rounded, size: 18, color: AppColors.sale),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          cart.couponCode!,
                          style: AppTypography.bodyMedium(weight: FontWeight.w700, color: AppColors.sale),
                        ),
                      ),
                      IconButton(
                        onPressed: () {
                          provider.removeCoupon();
                          _couponCtrl.clear();
                        },
                        icon: const Icon(Icons.close_rounded, size: 18),
                      ),
                    ] else ...[
                      Expanded(
                        child: TextField(
                          controller: _couponCtrl,
                          decoration: const InputDecoration(
                            labelText: 'Coupon code',
                            isDense: true,
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      FilledButton(
                        onPressed: _busy ? null : _applyCoupon,
                        style: FilledButton.styleFrom(
                          minimumSize: const Size(0, 44),
                          padding: const EdgeInsets.symmetric(horizontal: 16),
                        ),
                        child: _busy
                            ? const SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2))
                            : const Text('Apply'),
                      ),
                    ],
                  ],
                ),
              ),

              // Order summary
              const SizedBox(height: 16),
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: AppColors.paper,
                  borderRadius: BorderRadius.circular(4),
                  border: Border.all(color: AppColors.line),
                ),
                child: Column(
                  children: [
                    _SummaryRow(label: 'Subtotal', value: cart.totals.subtotal),
                    if (cart.totals.discount > 0)
                      _SummaryRow(label: 'Discount', value: -cart.totals.discount, emphasized: true),
                    _SummaryRow(label: 'Shipping', value: cart.totals.shipping > 0 ? cart.totals.shipping : 0),
                    const Divider(height: 20),
                    _SummaryRow(label: 'Total', value: cart.totals.total, bold: true),
                  ],
                ),
              ),
            ],
          ),
        ),
        // Bottom bar
        SafeArea(
          top: false,
          child: Container(
            padding: const EdgeInsets.fromLTRB(16, 10, 16, 12),
            decoration: const BoxDecoration(
              color: AppColors.paper,
              border: Border(top: BorderSide(color: AppColors.line)),
            ),
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text('Total', style: AppTypography.bodySmall()),
                      Text(
                        formatINR(cart.totals.total),
                        style: AppTypography.price(size: 19, color: AppColors.heading),
                      ),
                    ],
                  ),
                ),
                Expanded(
                  child: FilledButton(
                    onPressed: () => Navigator.of(context).pushNamed('/checkout'),
                    child: const Text('Checkout'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}

class _ShippingProgress extends StatelessWidget {
  const _ShippingProgress({required this.cart});

  final Cart cart;

  @override
  Widget build(BuildContext context) {
    final remaining = AppConfig.freeShippingThreshold - cart.totals.subtotal;

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.greySoft,
        borderRadius: BorderRadius.circular(4),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
Text(
            remaining > 0
                ? 'Add ${formatINR(remaining)} more to get FREE shipping'
                : 'You have FREE shipping on this order',
            style: AppTypography.bodyMedium(size: 13, weight: FontWeight.w600),
          ),
          const SizedBox(height: 8),
          ClipRRect(
            borderRadius: BorderRadius.circular(2),
            child: LinearProgressIndicator(
              value: (cart.totals.subtotal / AppConfig.freeShippingThreshold).clamp(0.0, 1.0),
              minHeight: 5,
              color: AppColors.gold,
              backgroundColor: AppColors.lineStrong,
            ),
          ),
        ],
      ),
    );
  }
}

class _CartItemTile extends StatelessWidget {
  const _CartItemTile({required this.item, required this.onQty, required this.onRemove});

  final CartItem item;
  final ValueChanged<int> onQty;
  final VoidCallback onRemove;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: AppColors.paper,
        borderRadius: BorderRadius.circular(4),
        border: Border.all(color: AppColors.line),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 82,
            height: 106,
            child: AppImage(url: item.product.imageUrl, borderRadius: BorderRadius.circular(3)),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: Text(
                        item.product.title,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: AppTypography.body(size: 13.5, color: AppColors.heading),
                      ),
                    ),
                    IconButton(
                      onPressed: onRemove,
                      icon: const Icon(Icons.close_rounded, size: 18, color: AppColors.muted),
                      visualDensity: VisualDensity.compact,
                    ),
                  ],
                ),
                if (item.variant != null)
                  Text(item.variant!.label, style: AppTypography.bodySmall(size: 11.5, color: AppColors.muted)),
                const SizedBox(height: 6),
                PriceText(price: item.unitPrice, size: 14, small: true, showDiscountBadge: false),
                const SizedBox(height: 8),
                QuantityStepper(
                  quantity: item.quantity,
                  onChanged: onQty,
                  max: (item.availableStock > 0 ? item.availableStock : 10).clamp(1, 10),
                  size: 30,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _SummaryRow extends StatelessWidget {
  const _SummaryRow({required this.label, required this.value, this.emphasized = false, this.bold = false});

  final String label;
  final double value;
  final bool emphasized;
  final bool bold;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: AppTypography.body(
              size: bold ? 15 : 13.5,
              color: emphasized ? AppColors.sale : AppColors.muted,
            ),
          ),
          Text(
            formatINR(value),
            style: (bold ? AppTypography.price(size: 15) : AppTypography.body(size: 13.5, weight: FontWeight.w600))
                .copyWith(color: emphasized ? AppColors.sale : AppColors.heading),
          ),
        ],
      ),
    );
  }
}