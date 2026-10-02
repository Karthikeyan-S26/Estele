import 'package:flutter/material.dart';

import '../../data/repositories/account_repository.dart';
import '../../models/order.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_typography.dart';
import '../../utils/formatters.dart';
import '../../widgets/load_state.dart';
import 'order_detail_screen.dart';

class OrdersScreen extends StatefulWidget {
  const OrdersScreen({super.key});

  @override
  State<OrdersScreen> createState() => _OrdersScreenState();
}

class _OrdersScreenState extends State<OrdersScreen> {
  List<Order>? _orders;
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
      final result = await AccountRepository.orders();
      if (mounted) {
        setState(() {
          _orders = result.items;
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
    return Scaffold(
      appBar: AppBar(title: const Text('My orders')),
      body: _loading
          ? const LoadState.loading()
          : _failed && _orders == null
          ? LoadState.error(message: 'Could not load orders.', onRetry: _load)
          : _orders!.isEmpty
          ? LoadState.empty(
              message: 'No orders yet. Start your collection today!',
            )
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView.builder(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16),
                itemCount: _orders!.length,
                itemBuilder: (context, i) {
                  final order = _orders![i];
                  return _OrderCard(
                    order: order,
                    onTap: () async {
                      await Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) =>
                              OrderDetailScreen(orderNumber: order.orderNumber),
                        ),
                      );
                      _load();
                    },
                  );
                },
              ),
            ),
    );
  }
}

class _OrderCard extends StatelessWidget {
  const _OrderCard({required this.order, required this.onTap});

  final Order order;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: AppColors.paper,
        borderRadius: BorderRadius.circular(4),
        border: Border.all(color: AppColors.line),
      ),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(4),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      order.orderNumber,
                      style: AppTypography.bodyMedium(weight: FontWeight.w700),
                    ),
                  ),
                  _StatusChip(status: order.status),
                ],
              ),
              const SizedBox(height: 6),
              Text(
                order.items.isEmpty
                    ? '${order.items.length} item · ${formatINR(order.total)}'
                    : '${order.items.first.productTitle}${order.items.length > 1 ? ' + ${order.items.length - 1} more' : ''} · ${formatINR(order.total)}',
                style: AppTypography.bodySmall(
                  size: 12.5,
                  color: AppColors.muted,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                'Placed ${order.placedAt?.day}/${order.placedAt?.month}/${order.placedAt?.year ?? '—'}',
                style: AppTypography.bodySmall(size: 11.5),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _StatusChip extends StatelessWidget {
  const _StatusChip({required this.status});

  final String status;

  @override
  Widget build(BuildContext context) {
    final cancelled = status == 'cancelled' || status == 'returned';
    final done = status == 'delivered';

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: cancelled
            ? AppColors.soldOut.withValues(alpha: 0.15)
            : done
            ? AppColors.success.withValues(alpha: 0.15)
            : AppColors.pinkSoft,
        borderRadius: BorderRadius.circular(2),
      ),
      child: Text(
        statusLabel(),
        style: AppTypography.bodySmall(
          size: 10.5,
          color: cancelled
              ? AppColors.soldOut
              : done
              ? AppColors.success
              : AppColors.accentDark,
          weight: FontWeight.w700,
        ),
      ),
    );
  }

  String statusLabel() {
    const map = {
      'placed': 'PLACED',
      'accepted': 'ACCEPTED',
      'packed': 'PACKED',
      'shipped': 'SHIPPED',
      'delivered': 'DELIVERED',
      'cancelled': 'CANCELLED',
      'returned': 'RETURNED',
      'processing': 'PROCESSING',
    };
    return map[status] ?? status.toUpperCase();
  }
}
