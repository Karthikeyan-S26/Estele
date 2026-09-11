import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../data/api_client.dart';
import '../../data/repositories/account_repository.dart';
import '../../models/wallet_transaction.dart';
import '../../providers/auth_provider.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_typography.dart';
import '../../utils/formatters.dart';
import '../../widgets/load_state.dart';

class WalletScreen extends StatefulWidget {
  const WalletScreen({super.key});

  @override
  State<WalletScreen> createState() => _WalletScreenState();
}

class _WalletScreenState extends State<WalletScreen> {
  final List<WalletTransaction> _transactions = [];
  bool _loading = true;
  bool _failed = false;
  bool _loadingMore = false;
  int _page = 1;
  bool _hasMore = false;

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
      final result = await AccountRepository.walletTransactions();
      if (mounted) {
        setState(() {
          _transactions
            ..clear()
            ..addAll(result.items);
          _page = 1;
          _hasMore = result.meta['has_more'] == true;
          _loading = false;
        });
      }
    } catch (_) {
      if (mounted) {
        setState(() {
          _failed = true;
          _loading = false;
        });
      }
    }
  }

  Future<void> _loadMore() async {
    if (_loadingMore || !_hasMore) return;
    _loadingMore = true;
    try {
      final result = await AccountRepository.walletTransactions(page: _page + 1);
      if (mounted) {
        setState(() {
          _transactions.addAll(result.items);
          _page += 1;
          _hasMore = result.meta['has_more'] == true;
        });
      }
    } on ApiException {
      // Keep the loaded rows; a later pull-to-refresh will retry.
    } finally {
      _loadingMore = false;
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final balance = auth.user?.walletBalance ?? 0;

    return Scaffold(
      appBar: AppBar(title: const Text('Estele wallet')),
      body: _loading
          ? const LoadState.loading()
          : _failed && _transactions.isEmpty
              ? LoadState.error(message: 'Could not load wallet transactions.', onRetry: _load)
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    children: [
                      // Balance card
                      Container(
                        padding: const EdgeInsets.all(20),
                        decoration: BoxDecoration(
                          color: AppColors.deepWine,
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'AVAILABLE BALANCE',
                              style: AppTypography.label(size: 10, color: AppColors.goldLight, letterSpacing: 1.6),
                            ),
                            const SizedBox(height: 8),
                            Text(
                              formatINR(balance),
                              style: AppTypography.sectionTitle(
                                size: 30,
                                color: Colors.white,
                                weight: FontWeight.w600,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 20),
                      Text('Transaction history', style: AppTypography.sectionTitle(size: 16)),
                      const SizedBox(height: 10),
                      if (_transactions.isEmpty)
                        const Padding(
                          padding: EdgeInsets.only(top: 24),
                          child: LoadState.empty(message: 'No wallet transactions yet.'),
                        )
                      else
                        for (final t in _transactions) _TransactionTile(transaction: t),
                      if (_loadingMore)
                        const Padding(
                          padding: EdgeInsets.symmetric(vertical: 12),
                          child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
                        ),
                    ],
                  ),
                ),
    );
  }
}

class _TransactionTile extends StatelessWidget {
  const _TransactionTile({required this.transaction});

  final WalletTransaction transaction;

  @override
  Widget build(BuildContext context) {
    final credit = transaction.isCredit;
    final expiring = transaction.status == 'expiring' && transaction.expiresAt != null;
    final expired = transaction.status == 'expired';

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.paper,
        borderRadius: BorderRadius.circular(4),
        border: Border.all(color: AppColors.line),
      ),
      child: Row(
        children: [
          Icon(
            credit ? Icons.add_circle_outline : Icons.remove_circle_outline,
            color: credit ? AppColors.success : AppColors.accentDark,
            size: 20,
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        _reasonLabel(transaction.reason),
                        style: AppTypography.bodyMedium(size: 12.5, weight: FontWeight.w600),
                      ),
                    ),
                    if (expired)
                      _Badge(label: 'EXPIRED', color: AppColors.soldOut)
                    else if (expiring)
                      _Badge(label: 'EXPIRING', color: AppColors.info),
                  ],
                ),
                const SizedBox(height: 2),
                Text(
                  _dateLabel(transaction.createdAt),
                  style: AppTypography.bodySmall(size: 11, color: AppColors.muted),
                ),
                if (expiring)
                  Padding(
                    padding: const EdgeInsets.only(top: 2),
                    child: Text(
                      'Credits expire ${_dateLabel(transaction.expiresAt)}',
                      style: AppTypography.bodySmall(size: 11, color: AppColors.accentDark),
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Text(
            '${credit ? '+' : '-'}${formatINR(transaction.amount)}',
            style: AppTypography.sectionTitle(
              size: 15,
              weight: FontWeight.w700,
              color: credit ? AppColors.success : AppColors.accentDark,
            ),
          ),
        ],
      ),
    );
  }

  String _reasonLabel(String reason) {
    switch (reason) {
      case 'sell_settlement':
        return 'Old jewellery settlement';
      case 'order_payment':
        return 'Checkout payment';
      case 'order_refund':
        return 'Order refund';
      case 'wallet_expiry':
        return 'Credit expired';
      case 'admin_credit':
        return 'Admin credit';
      case 'admin_debit':
        return 'Admin debit';
      default:
        return reason.isEmpty ? 'Wallet transaction' : reason;
    }
  }

  String _dateLabel(DateTime? dt) {
    if (dt == null) return '—';
    final local = dt.toLocal();
    final hh = local.hour.toString().padLeft(2, '0');
    final mm = local.minute.toString().padLeft(2, '0');
    return '${local.day}/${local.month}/${local.year} · $hh:$mm';
  }
}

class _Badge extends StatelessWidget {
  const _Badge({required this.label, required this.color});

  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.14),
        borderRadius: BorderRadius.circular(2),
      ),
      child: Text(
        label,
        style: AppTypography.label(size: 9.5, color: color, weight: FontWeight.w700, letterSpacing: 0.6),
      ),
    );
  }
}