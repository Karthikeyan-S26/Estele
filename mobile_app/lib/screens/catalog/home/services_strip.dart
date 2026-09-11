import 'package:flutter/material.dart';

import '../../../models/home_data.dart';
import '../../../theme/app_colors.dart';
import '../../../theme/app_typography.dart';

/// The thin "Why shop with us" trust strip (anti-tarnish / returns / shipping /
/// secure payments) from the footer_usps block.
class ServicesStrip extends StatelessWidget {
  const ServicesStrip({super.key, required this.services});

  final List<ServiceBenefit> services;

  static const _icons = <String, IconData>{
    'shield': Icons.verified_user_outlined,
    'return': Icons.autorenew_rounded,
    'truck': Icons.local_shipping_outlined,
    'lock': Icons.lock_outline_rounded,
  };

  @override
  Widget build(BuildContext context) {
    if (services.isEmpty) return const SizedBox.shrink();

    return Container(
      margin: const EdgeInsets.fromLTRB(16, 26, 16, 0),
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 16),
      decoration: BoxDecoration(
        color: AppColors.ivory,
        borderRadius: BorderRadius.circular(4),
        border: Border.all(color: AppColors.line),
      ),
      child: Row(
        children: List.generate(services.length, (i) {
          final service = services[i];
          final icon = _icons[service.icon] ?? Icons.check_circle_outline;
          return Expanded(
            child: Column(
              children: [
                Icon(icon, size: 20, color: AppColors.accent),
                const SizedBox(height: 6),
                Text(
                  service.title ?? '',
                  textAlign: TextAlign.center,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: AppTypography.bodySmall(size: 9.5, color: AppColors.ink, weight: FontWeight.w600),
                ),
              ],
            ),
          );
        }),
      ),
    );
  }
}