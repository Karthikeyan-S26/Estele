import 'package:flutter/material.dart';

import '../../data/repositories/sell_repository.dart';
import '../../models/sell_request.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_typography.dart';
import '../../utils/formatters.dart';
import '../../widgets/load_state.dart';
import 'sell_create_screen.dart';
import 'sell_detail_screen.dart';

class SellScreen extends StatefulWidget {
  const SellScreen({super.key});

  @override
  State<SellScreen> createState() => _SellScreenState();
}

class _SellScreenState extends State<SellScreen> {
  List<SellRequest>? _requests;
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
      final requests = await SellRepository.list();
      if (mounted) {
        setState(() {
          _requests = requests;
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

  Future<void> _openCreate() async {
    await Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => const SellCreateScreen()),
    );
    _load();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Sell gold')),
      body: _loading
          ? const LoadState.loading()
          : _failed && _requests == null
              ? LoadState.error(message: 'Could not load your sell requests.', onRetry: _load)
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    children: [
                      _IntroCard(onStart: _openCreate),
                      const SizedBox(height: 16),
                      Row(
                        children: [
                          Expanded(child: Text('My requests', style: AppTypography.sectionTitle(size: 16))),
                          TextButton.icon(
                            onPressed: _openCreate,
                            icon: const Icon(Icons.add_rounded, size: 18),
                            label: const Text('New request'),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      if (_requests == null || _requests!.isEmpty)
                        const Padding(
                          padding: EdgeInsets.only(top: 20),
                          child: LoadState.empty(message: 'No sell requests yet. Start one and get cash offers from our trusted buyers.'),
                        )
                      else
                        for (final request in _requests!) _SellCard(request: request, onVisit: _load),
                    ],
                  ),
                ),
    );
  }
}

class _IntroCard extends StatelessWidget {
  const _IntroCard({required this.onStart});

  final VoidCallback onStart;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.deepWine,
        borderRadius: BorderRadius.circular(6),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('YOUR OLD GOLD, NEW PEACE OF MIND', style: AppTypography.label(size: 10, color: AppColors.goldLight, letterSpacing: 1.4)),
          const SizedBox(height: 8),
          Text(
            'Send us a photo and a short video of your old jewellery. Trusted buyers bid on it within hours — you choose to accept the best offer.',
            style: AppTypography.body(size: 13, color: Colors.white70, height: 1.45),
          ),
          const SizedBox(height: 14),
          FilledButton(
            onPressed: onStart,
            style: FilledButton.styleFrom(
              backgroundColor: AppColors.gold,
              foregroundColor: AppColors.deepWine,
            ),
            child: const Text('Start a sell request'),
          ),
        ],
      ),
    );
  }
}

class _SellCard extends StatelessWidget {
  const _SellCard({required this.request, required this.onVisit});

  final SellRequest request;
  final VoidCallback onVisit;

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
        borderRadius: BorderRadius.circular(4),
        onTap: () async {
          await Navigator.of(context).push(
            MaterialPageRoute(builder: (_) => SellDetailScreen(requestNumber: request.requestNumber)),
          );
          if (context.mounted) onVisit();
        },
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(request.requestNumber, style: AppTypography.bodyMedium(weight: FontWeight.w700)),
                  ),
                  _StatusChip(status: request.status),
                ],
              ),
              const SizedBox(height: 6),
              Text(
                '${_itemTypeLabel(request.itemType)} · ${request.city ?? '—'}',
                style: AppTypography.body(size: 13.5, color: AppColors.muted),
              ),
              const SizedBox(height: 6),
              Text(
                request.highestBidAmount != null
                    ? 'Highest offer ${formatINR(request.highestBidAmount!)} · ${request.bidCount} bid${request.bidCount == 1 ? '' : 's'}'
                    : '${request.status == 'bidding' && request.bidCount == 0 ? 'No bids yet — buyers are reviewing' : 'Awaiting buyers'}',
                style: AppTypography.bodySmall(size: 12),
              ),
              const SizedBox(height: 4),
              Text(
                '${request.createdAt?.toLocal().day}/${request.createdAt?.toLocal().month}/${request.createdAt?.toLocal().year ?? ''}',
                style: AppTypography.bodySmall(size: 11.5, color: AppColors.muted),
              ),
            ],
          ),
        ),
      ),
    );
  }

  String _itemTypeLabel(String itemType) {
    const map = {
      'ring': 'Ring',
      'chain': 'Chain',
      'necklace': 'Necklace',
      'earrings': 'Earrings',
      'bracelet': 'Bracelet',
      'other': 'Other',
    };
    return map[itemType] ?? itemType;
  }
}

class _StatusChip extends StatelessWidget {
  const _StatusChip({required this.status});

  final String status;

  @override
  Widget build(BuildContext context) {
    final Color color;
    switch (status) {
      case 'completed':
        color = AppColors.success;
      case 'bidding':
        color = AppColors.accentDark;
      case 'cancelled':
      case 'expired':
        color = AppColors.soldOut;
      default:
        color = AppColors.info;
    }

    const labels = {
      'pending_bids': 'WAITING',
      'bidding': 'BIDDING',
      'valuation_review': 'REVIEW',
      'completed': 'DONE',
      'expired': 'EXPIRED',
      'cancelled': 'CANCELLED',
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.14),
        borderRadius: BorderRadius.circular(2),
      ),
      child: Text(
        labels[status] ?? status.toUpperCase(),
        style: AppTypography.bodySmall(size: 10.5, color: color, weight: FontWeight.w700),
      ),
    );
  }
}