import 'package:flutter/material.dart';

import '../../models/order.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_typography.dart';
import '../../utils/formatters.dart';

/// Shown right after a successful order (COD in this build). Renders the
/// order number, a status note, a summary, and order tracking CTA.
class OrderConfirmationScreen extends StatelessWidget {
  const OrderConfirmationScreen({super.key, required this.order});

  final Order order;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Order placed')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          const SizedBox(height: 12),
          const Icon(
            Icons.check_circle_rounded,
            size: 64,
            color: AppColors.success,
          ),
          const SizedBox(height: 14),
          Center(
            child: Text(
              'Thank you! Your order is confirmed',
              style: AppTypography.editorial(size: 22),
            ),
          ),
          const SizedBox(height: 8),
          Center(
            child: Text(
              'Order number: ${order.orderNumber}',
              style: AppTypography.bodyMedium(
                weight: FontWeight.w600,
                color: AppColors.accentDark,
              ),
            ),
          ),
          const SizedBox(height: 24),

          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: AppColors.paper,
              borderRadius: BorderRadius.circular(4),
              border: Border.all(color: AppColors.line),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Order summary',
                  style: AppTypography.sectionTitle(size: 16),
                ),
                const SizedBox(height: 10),
                for (final item in order.items)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          width: 46,
                          height: 58,
                          alignment: Alignment.center,
                          decoration: BoxDecoration(
                            color: AppColors.warmBeige,
                            borderRadius: BorderRadius.circular(3),
                          ),
                          child: Text(
                            item.productTitle.isNotEmpty
                                ? item.productTitle
                                      .trim()
                                      .substring(0, 1)
                                      .toUpperCase()
                                : 'J',
                            style: AppTypography.sectionTitle(
                              size: 18,
                              color: AppColors.accentDark,
                            ),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                item.productTitle,
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                                style: AppTypography.body(size: 13),
                              ),
                              Text(
                                'Qty ${item.quantity} · ${formatINR(item.subtotal)}',
                                style: AppTypography.bodySmall(size: 12),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                const Divider(height: 16),
                _Row(label: 'Subtotal', value: order.subtotal),
                if (order.discountAmount > 0)
                  _Row(
                    label:
                        'Discount${order.couponCode != null ? ' (${order.couponCode})' : ''}',
                    value: -order.discountAmount,
                    sale: true,
                  ),
                _Row(label: 'Shipping', value: order.shippingFee),
                _Row(label: 'Total', value: order.total, bold: true),
              ],
            ),
          ),

          const SizedBox(height: 16),
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: AppColors.greySoft,
              borderRadius: BorderRadius.circular(4),
            ),
            child: Row(
              children: [
                const Icon(
                  Icons.local_shipping_outlined,
                  color: AppColors.accent,
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    'Payment: ${order.paymentMethod == 'COD' ? 'Cash on Delivery' : 'Online'} '
                    '· Status: ${order.statusLabel}',
                    style: AppTypography.body(size: 13),
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 24),
          FilledButton(
            onPressed: () {
              Navigator.of(context).popUntil((r) => r.isFirst);
              Navigator.of(context).pushNamed('/orders');
            },
            child: const Text('Track my orders'),
          ),
          TextButton(
            onPressed: () => Navigator.of(context).popUntil((r) => r.isFirst),
            child: const Text('Back to shopping'),
          ),
        ],
      ),
    );
  }
}

class _Row extends StatelessWidget {
  const _Row({
    required this.label,
    required this.value,
    this.sale = false,
    this.bold = false,
  });

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
          Text(
            label,
            style: AppTypography.body(
              size: 13.5,
              color: sale ? AppColors.sale : AppColors.muted,
            ),
          ),
          Text(
            formatINR(value),
            style:
                (bold
                        ? AppTypography.price(size: 15)
                        : AppTypography.body(
                            size: 13.5,
                            weight: FontWeight.w600,
                          ))
                    .copyWith(color: sale ? AppColors.sale : AppColors.heading),
          ),
        ],
      ),
    );
  }
}
