import 'package:flutter/material.dart';
import 'package:video_player/video_player.dart';

import '../../data/repositories/sell_repository.dart';
import '../../models/sell_request.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_typography.dart';
import '../../utils/formatters.dart';
import '../../widgets/load_state.dart';

class SellDetailScreen extends StatefulWidget {
  const SellDetailScreen({super.key, required this.requestNumber});

  final String requestNumber;

  @override
  State<SellDetailScreen> createState() => _SellDetailScreenState();
}

class _SellDetailScreenState extends State<SellDetailScreen> {
  SellRequest? _request;
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
      final request = await SellRepository.show(widget.requestNumber);
      if (mounted) {
        setState(() {
          _request = request;
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

  Future<void> _confirmCancel() async {
    final reasonCtrl = TextEditingController();
    final reason = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Cancel this request?'),
        content: TextField(
          controller: reasonCtrl,
          maxLines: 2,
          maxLength: 255,
          decoration: const InputDecoration(
            labelText: 'Reason (optional)',
            hintText: 'No particular reason',
            border: OutlineInputBorder(),
            counterText: '',
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Keep it'),
          ),
          FilledButton(
            onPressed: () => Navigator.of(context).pop(reasonCtrl.text.trim()),
            style: FilledButton.styleFrom(backgroundColor: AppColors.error),
            child: const Text('Cancel request'),
          ),
        ],
      ),
    );
    if (reason == null || !mounted) return;

    setState(() => _cancelling = true);
    try {
      await SellRepository.cancel(widget.requestNumber, reason);
      if (!mounted) return;
      await _load();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Could not cancel: ${e.toString().replaceAll('ApiException', '').trim()}')),
      );
    } finally {
      if (mounted) setState(() => _cancelling = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final request = _request;
    return Scaffold(
      appBar: AppBar(title: const Text('Sell request')),
      body: _loading
          ? const LoadState.loading()
          : _failed || request == null
              ? LoadState.error(message: 'Could not load this sell request.', onRetry: _load)
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(request.requestNumber, style: AppTypography.sectionTitle(size: 17)),
                                Text(
                                  '${_itemTypeLabel(request.itemType)}${request.city != null ? ' · ${request.city}' : ''}',
                                  style: AppTypography.bodySmall(color: AppColors.muted),
                                ),
                              ],
                            ),
                          ),
                          _StatusChip(status: request.status),
                        ],
                      ),
                      const SizedBox(height: 14),

                      if (request.imageUrl != null) ...[
                        ClipRRect(
                          borderRadius: BorderRadius.circular(4),
                          child: Image.network(
                            request.imageUrl!,
                            height: 180,
                            width: double.infinity,
                            fit: BoxFit.cover,
                            errorBuilder: (context, error, stackTrace) => const _MediaPlaceholder(icon: Icons.image_outlined),
                          ),
                        ),
                        const SizedBox(height: 10),
                      ],
                      if (request.videoUrl != null)
                        SellVideoPlayer(videoUrl: request.videoUrl!),

                      const SizedBox(height: 14),
                      if (request.status == 'bidding' && request.biddingOpen) ...[
                        _HighlightCard(
                          title: 'BIDDING IS OPEN',
                          subtitle: _closesLabel(request.bidsEndAt),
                          leading: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                request.highestBidAmount != null
                                    ? formatINR(request.highestBidAmount!)
                                    : 'No offers yet',
                                style: AppTypography.sectionTitle(size: 26, color: AppColors.deepWine),
                              ),
                              Text(
                                'Highest offer · ${request.bidCount} bid${request.bidCount == 1 ? '' : 's'}',
                                style: AppTypography.bodySmall(size: 11.5),
                              ),
                            ],
                          ),
                        ),
                      ] else if (request.highestBidAmount != null && request.status != 'cancelled') ...[
                        _HighlightCard(
                          title: 'OFFERS RECEIVED',
                          leading: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(formatINR(request.highestBidAmount!), style: AppTypography.sectionTitle(size: 26, color: AppColors.deepWine)),
                              Text('${request.bidCount} bid${request.bidCount == 1 ? '' : 's'}',
                                  style: AppTypography.bodySmall(size: 11.5)),
                            ],
                          ),
                        ),
                      ],

                      if (request.status == 'completed') ...[
                        const SizedBox(height: 12),
                        _SettlementCard(request: request),
                      ],

                      if (request.cancelReason != null)
                        Padding(
                          padding: const EdgeInsets.only(top: 12),
                          child: Text(
                            'Cancelled: ${request.cancelReason}',
                            style: AppTypography.bodySmall(color: AppColors.soldOut),
                          ),
                        ),

                      if (request.description != null && request.description!.isNotEmpty)
                        Padding(
                          padding: const EdgeInsets.only(top: 14),
                          child: Text(request.description!, style: AppTypography.body(size: 13.5)),
                        ),

                      if (request.canCancel)
                        Padding(
                          padding: const EdgeInsets.only(top: 16),
                          child: OutlinedButton.icon(
                            onPressed: _cancelling ? null : _confirmCancel,
                            icon: const Icon(Icons.close_rounded, size: 18),
                            label: const Text('Cancel this request'),
                            style: OutlinedButton.styleFrom(foregroundColor: AppColors.error),
                          ),
                        ),

                      const SizedBox(height: 20),
                      Text('Activity', style: AppTypography.label(letterSpacing: 1.2)),
                      const SizedBox(height: 8),
                      if (request.timeline.isEmpty)
                        const LoadState.empty(message: 'No activity yet.')
                      else
                        ...request.timeline.map(_TimelineRow.new).toList(),

                      const SizedBox(height: 20),
                    ],
                  ),
                ),
    );
  }

  String _closesLabel(DateTime? end) {
    if (end == null) return 'Bidding window ended';
    final remaining = end.difference(DateTime.now());
    if (remaining.isNegative) return 'Bidding window ended';
    final h = remaining.inHours;
    final m = remaining.inMinutes.remainder(60);
    return h > 0 ? 'Closes in ${h}h ${m}m' : 'Closes in ${remaining.inMinutes.remainder(60)}m';
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

class _HighlightCard extends StatelessWidget {
  const _HighlightCard({required this.title, required this.leading, this.subtitle});

  final String title;
  final Widget leading;
  final String? subtitle;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.deepWine,
        borderRadius: BorderRadius.circular(6),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: AppTypography.label(size: 10, color: AppColors.goldLight, letterSpacing: 1.4)),
          const SizedBox(height: 8),
          leading,
          if (subtitle != null) ...[
            const SizedBox(height: 4),
            Text(subtitle!, style: AppTypography.bodySmall(size: 11.5, color: Colors.white70)),
          ],
        ],
      ),
    );
  }
}

class _SettlementCard extends StatelessWidget {
  const _SettlementCard({required this.request});

  final SellRequest request;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.success.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(6),
        border: Border.all(color: AppColors.success.withValues(alpha: 0.4)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('SETTLEMENT COMPLETED', style: AppTypography.label(size: 10, color: AppColors.success, letterSpacing: 1.2)),
          const SizedBox(height: 10),
          _row('Valuation', request.adminValuation != null ? formatINR(request.adminValuation!) : '—'),
          _row('Service deduction', request.deductionAmount != null ? '− ${formatINR(request.deductionAmount!)}' : '—'),
          const Divider(height: 18, color: AppColors.success),
          _row('Credited to your wallet', request.walletCredit != null ? formatINR(request.walletCredit!) : '—', bold: true),
        ],
      ),
    );
  }

  Widget _row(String label, String value, {bool bold = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: AppTypography.bodySmall(size: 12.5)),
          Text(
            value,
            style: AppTypography.bodyMedium(
              size: 13.5,
              weight: bold ? FontWeight.w700 : FontWeight.w600,
              color: AppColors.success,
            ),
          ),
        ],
      ),
    );
  }
}

class _StatusChip extends StatelessWidget {
  const _StatusChip({required this.status});

  final String status;

  @override
  Widget build(BuildContext context) {
    const labels = {
      'pending_bids': 'WAITING FOR SELLERS',
      'bidding': 'BIDDING OPEN',
      'valuation_review': 'UNDER REVIEW',
      'completed': 'COMPLETED',
      'expired': 'EXPIRED',
      'cancelled': 'CANCELLED',
    };
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
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.14),
        borderRadius: BorderRadius.circular(2),
      ),
      child: Text(
        labels[status] ?? status.toUpperCase(),
        style: AppTypography.bodySmall(size: 10, color: color, weight: FontWeight.w700),
      ),
    );
  }
}

class _MediaPlaceholder extends StatelessWidget {
  const _MediaPlaceholder({required this.icon});

  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 180,
      color: AppColors.greySoft,
      child: Center(child: Icon(icon, size: 40, color: AppColors.lineStrong)),
    );
  }
}

class _TimelineRow extends StatelessWidget {
  const _TimelineRow(this.event);

  final SellTimelineEvent event;

  @override
  Widget build(BuildContext context) {
    final (icon, label) = switch (event.event) {
      'created' => (Icons.add_rounded, 'Request created'),
      'vendor_invited' => (Icons.mail_outline_rounded, 'Buyer invited'),
      'vendor_accepted' => (Icons.handshake_outlined, 'Buyer accepted'),
      'vendor_declined' => (Icons.do_not_disturb_on_outlined, 'Buyer declined'),
      'bidding_opened' => (Icons.gavel_rounded, 'Bidding opened'),
      'bid_submitted' => (Icons.currency_rupee_rounded, 'Bid received'),
      'bid_updated' => (Icons.currency_rupee_rounded, 'Bid updated'),
      'bids_closed' => (Icons.lock_clock_outlined, 'Bidding closed'),
      'settlement_completed' => (Icons.verified_rounded, 'Settlement completed'),
      'cancelled_by_customer' => (Icons.close_rounded, 'Cancelled — you'),
      'cancelled_by_admin' => (Icons.close_rounded, 'Cancelled by Estele'),
      _ => (Icons.circle_outlined, event.event.replaceAll('_', ' ')),
    };

    String? amount;
    if (event.event == 'bid_submitted' || event.event == 'bid_updated') {
      final raw = event.metadata['amount'];
      if (raw != null) amount = formatINR(raw is num ? raw.toDouble() : double.tryParse(raw.toString()) ?? 0);
    }

    final at = event.at?.toLocal();

    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(4),
            decoration: BoxDecoration(
              color: AppColors.pinkSoft,
              borderRadius: BorderRadius.circular(99),
            ),
            child: Icon(icon, size: 15, color: AppColors.accentDark),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(child: Text(label, style: AppTypography.bodyMedium(size: 13, weight: FontWeight.w600))),
                    if (amount != null)
                      Text(amount, style: AppTypography.bodyMedium(size: 13, weight: FontWeight.w700, color: AppColors.success)),
                  ],
                ),
                Text(
                  at == null ? '' : '${at.day}/${at.month}/${at.year} · ${at.hour.toString().padLeft(2, '0')}:${at.minute.toString().padLeft(2, '0')}',
                  style: AppTypography.bodySmall(size: 11, color: AppColors.muted),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// Inline player for the short jewellery video attached to a sell request.
class SellVideoPlayer extends StatefulWidget {
  const SellVideoPlayer({super.key, required this.videoUrl});

  final String videoUrl;

  @override
  State<SellVideoPlayer> createState() => _SellVideoPlayerState();
}

class _SellVideoPlayerState extends State<SellVideoPlayer> {
  VideoPlayerController? _controller;
  bool _failed = false;

  @override
  void initState() {
    super.initState();
    _init();
  }

  Future<void> _init() async {
    final controller = VideoPlayerController.networkUrl(Uri.parse(widget.videoUrl));
    _controller = controller;
    try {
      await controller.initialize();
      await controller.setLooping(false);
      if (!mounted) return;
      setState(() {});
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _failed = true;
      });
    }
  }

  @override
  void dispose() {
    _controller?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final controller = _controller;
    final ratio = (controller != null && controller.value.isInitialized && controller.value.aspectRatio > 0)
        ? controller.value.aspectRatio
        : 16 / 9;
    return ClipRRect(
      borderRadius: BorderRadius.circular(4),
      child: AspectRatio(
        aspectRatio: ratio,
        child: Container(
          color: Colors.black,
          child: _failed
              ? const _MediaPlaceholder(icon: Icons.videocam_outlined)
              : controller == null || !controller.value.isInitialized
                  ? const Center(
                      child: SizedBox(
                        width: 26,
                        height: 26,
                        child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                      ),
                    )
                  : GestureDetector(
                      onTap: () {
                        setState(() {
                          controller.value.isPlaying ? controller.pause() : controller.play();
                        });
                      },
                      child: Stack(
                        fit: StackFit.expand,
                        children: [
                          VideoPlayer(controller),
                          if (!controller.value.isPlaying)
                            const Center(
                              child: Icon(Icons.play_circle_fill, size: 52, color: Colors.white70),
                            ),
                        ],
                      ),
                    ),
        ),
      ),
    );
  }
}