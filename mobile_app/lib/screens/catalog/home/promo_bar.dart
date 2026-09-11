import 'dart:async';

import 'package:flutter/material.dart';

import '../../../theme/app_colors.dart';
import '../../../theme/app_typography.dart';

/// The announcement bar: cycles through the promo messages from backend
/// (offer texts / announcement_messages) with a gentle fade.
class PromoBar extends StatefulWidget {
  const PromoBar({super.key, required this.messages});

  final List<String> messages;

  @override
  State<PromoBar> createState() => _PromoBarState();
}

class _PromoBarState extends State<PromoBar> {
  int _index = 0;
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    if (widget.messages.length > 1) {
      _timer = Timer.periodic(const Duration(seconds: 3), (_) {
        setState(() => _index = (_index + 1) % widget.messages.length);
      });
    }
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      color: AppColors.deepWine,
      padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 12),
      child: Row(
        children: [
          const Icon(Icons.local_offer_outlined, size: 15, color: AppColors.gold),
          const SizedBox(width: 8),
          Expanded(
            child: AnimatedSwitcher(
              duration: const Duration(milliseconds: 350),
              child: Text(
                widget.messages[_index % widget.messages.length],
                key: ValueKey(_index),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: AppTypography.bodySmall(size: 11.5, color: AppColors.goldLight),
              ),
            ),
          ),
        ],
      ),
    );
  }
}