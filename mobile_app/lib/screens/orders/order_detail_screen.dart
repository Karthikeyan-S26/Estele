import 'package:flutter/material.dart';

import '../../data/repositories/account_repository.dart';
import '../../data/repositories/checkout_repository.dart';
import '../../models/order.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_typography.dart';
import '../../utils/formatters.dart';
import '../../widgets/load_state.dart';

class OrderDetailScreen extends StatefulWidget {
  const OrderDetailScreen({super.key, required this.orderNumber});

  final String orderNumber;

  @override
  State<OrderDetailScreen> createState() => _OrderDetailScreenState();
}

class _OrderDetailScreenState extends State<OrderDetailScreen> {
  Order? _order;
  bool _loading = true;
  bool _failed = false;
  bool _cancelling = false;

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
      final order = await CheckoutRepository.orderByNumber(widget.orderNumber);
      if (mounted) {
        setState(() {
          _order = order;
          _loading = false;
        });
      }
    } catch (_) {
      if (mounted) setState(() {
        _failed = true;
        _loading = false;
      });
    }
  }

  Future<void> _cancelOrder() async {
    final reason = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Cancel order'),
        content: const Text('Your order will be cancelled and your refund (if paid) processed back to source.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Keep order'),
          ),
          TextButton(
            onPressed: () => Navigator.of(context).pop('cancel'),
            child: const Text('Cancel order', style: TextStyle(color: AppColors.error)),
          ),
        ],
      ),
    );
    if (reason != 'cancel') return;

    setState(() => _cancelling = true);
    try {
      final message = await AccountRepository.requestOrderCancellation(widget.orderNumber);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(message ?? 'Cancellation requested')),
        );
        _load();
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString())),
        );
        setState(() => _cancelling = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Order details')),
      body: _loading
          ? const LoadState.loading()
          : _failed || _order == null
              ? LoadState.error(message: 'Could not load this order.', onRetry: _load)
              : _buildBody(_order!),
    );
  }

  Widget _buildBody(Order order) {
    final isCancelled = order.status == 'cancelled' || order.status == 'returned';

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        // Status banner
        Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: AppColors.paper,
            borderRadius: BorderRadius.circular(4),
            border: Border.all(color: AppColors.line),
          ),
          child: Row(
            children: [
              const Icon(Icons.receipt_long_outlined, color: AppColors.accent),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(order.orderNumber, style: AppTypography.bodyMedium(weight: FontWeight.w700)),
                    Text('Status: ${order.statusLabel}', style: AppTypography.bodySmall(size: 12.5)),
                  ],
                ),
              ),
            ],
          ),
        ),

        if (isCancelled) ...[
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: AppColors.soldOut.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(4),
            ),
            child: Text(
              order.cancellationReason?.isNotEmpty == true
                  ? 'Reason: ${order.cancellationReason}'
                  : 'This order is cancelled.',
              style: AppTypography.bodySmall(size: 12.5, color: AppColors.soldOut),
            ),
          ),
        ],

        const SizedBox(height: 16),
        Text('Items', style: AppTypography.sectionTitle(size: 16)),
        const SizedBox(height: 8),
        Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: AppColors.paper,
            borderRadius: BorderRadius.circular(4),
            border: Border.all(color: AppColors.line),
          ),
          child: Column(
            children: [
              for (final item in order.items)
                Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Container(
                        width: 44,
                        height: 56,
                        alignment: Alignment.center,
                        decoration: BoxDecoration(
                          color: AppColors.warmBeige,
                          borderRadius: BorderRadius.circular(3),
                        ),
                        child: Text(
                          item.productTitle.isNotEmpty
                              ? item.productTitle.trim().substring(0, 1).toUpperCase()
                              : 'J',
                          style: AppTypography.sectionTitle(size: 16, color: AppColors.accentDark),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(item.productTitle, style: AppTypography.body(size: 13.5)),
                            Text(
                              'SKU ${item.sku} · Qty ${item.quantity}',
                              style: AppTypography.bodySmall(size: 11.5, color: AppColors.muted),
                            ),
                          ],
                        ),
                      ),
                      Text(formatINR(item.subtotal), style: AppTypography.bodyMedium(weight: FontWeight.w600)),
                    ],
                  ),
                ),
            ],
          ),
        ),

        const SizedBox(height: 16),
        Text('Price summary', style: AppTypography.sectionTitle(size: 16)),
        const SizedBox(height: 8),
        Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: AppColors.paper,
            borderRadius: BorderRadius.circular(4),
            border: Border.all(color: AppColors.line),
          ),
          child: Column(
            children: [
              _Row(label: 'Subtotal', value: order.subtotal),
              if (order.discountAmount > 0)
                _Row(
                  label: 'Discount${order.couponCode != null ? ' (${order.couponCode})' : ''}',
                  value: -order.discountAmount,
                  sale: true,
                ),
              _Row(label: 'Shipping', value: order.shippingFee),
              if (order.walletAmountUsed > 0) _Row(label: 'Wallet used', value: -order.walletAmountUsed, sale: true),
              const Divider(height: 16),
              _Row(label: 'Total', value: order.total, bold: true),
            ],
          ),
        ),

        const SizedBox(height: 16),
        if (order.shippingAddress != null)
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: AppColors.greySoft,
              borderRadius: BorderRadius.circular(4),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Icon(Icons.location_on_outlined, color: AppColors.accent),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Deliver to ${order.customerName}',
                        style: AppTypography.bodyMedium(weight: FontWeight.w600),
                      ),
                      Text(
                        order.shippingAddress!.singleLine,
                        style: AppTypography.bodySmall(size: 12.5),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),

        const SizedBox(height: 16),
        if (_cancelling)
          const Center(child: CircularProgressIndicator(strokeWidth: 2))
        else if (order.canRequestCancellation)
          OutlinedButton.icon(
            onPressed: _cancelOrder,
            icon: const Icon(Icons.cancel_outlined, size: 18),
            label: const Text('Request cancellation'),
            style: OutlinedButton.styleFrom(foregroundColor: AppColors.error),
          ),
        const SizedBox(height: 20),
      ],
    );
  }
}

class _Row extends StatelessWidget {
  const _Row({required this.label, required this.value, this.sale = false, this.bold = false});

  final String label;
  final double value;
  final bool sale;
  final bool bold;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: AppTypography.body(size: 13.5, color: sale ? AppColors.sale : AppColors.muted)),
          Text(
            formatINR(value),
            style: (bold ? AppTypography.price(size: 15) : AppTypography.body(size: 13.5, weight: FontWeight.w600))
                .copyWith(color: sale ? AppColors.sale : AppColors.heading),
          ),
        ],
      ),
    );
  }
}