import 'package:flutter/material.dart';

import '../../../models/home_data.dart';
import '../../../theme/app_colors.dart';
import '../../../theme/app_typography.dart';

/// Site footer (company info, contact, popular searches, payment badges)
/// rendered at the bottom of the home list.
class FooterBlock extends StatelessWidget {
  const FooterBlock({super.key, required this.footer});

  final FooterData footer;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      margin: const EdgeInsets.only(top: 28),
      padding: const EdgeInsets.fromLTRB(20, 24, 20, 28),
      color: AppColors.deepWine,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Estele',
            style: AppTypography.scriptAccent(size: 30, color: AppColors.goldLight),
          ),
          const SizedBox(height: 4),
          if (footer.companyName != null)
            Text(
              footer.companyName!,
              style: AppTypography.bodySmall(size: 11, color: Colors.white70),
            ),
          const SizedBox(height: 12),
          if (footer.about != null) ...[
            Text(
              footer.about!,
              style: AppTypography.bodySmall(size: 11, color: Colors.white70).copyWith(height: 1.5),
            ),
            const SizedBox(height: 14),
          ],
          const Divider(color: AppColors.gold, thickness: 1),
          const SizedBox(height: 8),
          _InfoRow(icon: Icons.phone_outlined, text: footer.contactPhone),
          if (footer.contactPhone != null) const SizedBox(height: 8),
          _InfoRow(icon: Icons.mail_outline, text: footer.contactEmail),
          if (footer.contactEmail != null) const SizedBox(height: 8),
          _InfoRow(icon: Icons.location_on_outlined, text: footer.contactAddress),
          if (footer.contactAddress != null) const SizedBox(height: 8),
          _InfoRow(icon: Icons.schedule_outlined, text: footer.contactHours),
          if (footer.popularSearches.isNotEmpty) ...[
            const SizedBox(height: 16),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: footer.popularSearches
                  .map((s) => Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                        decoration: BoxDecoration(
                          border: Border.all(color: AppColors.gold.withValues(alpha: 0.5)),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Text(
                          s,
                          style: AppTypography.bodySmall(size: 10.5, color: AppColors.goldLight),
                        ),
                      ))
                  .toList(),
            ),
          ],
          if (footer.paymentBadges.isNotEmpty) ...[
            const SizedBox(height: 16),
            Text(
              'We accept',
              style: AppTypography.label(size: 10, color: Colors.white70),
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: footer.paymentBadges
                  .map((p) => Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.08),
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: Text(
                          p,
                          style: AppTypography.bodySmall(size: 9.5, color: Colors.white),
                        ),
                      ))
                  .toList(),
            ),
          ],
          if (footer.copyright != null) ...[
            const SizedBox(height: 20),
            Center(
              child: Text(
                footer.copyright!,
                textAlign: TextAlign.center,
                style: AppTypography.bodySmall(size: 10, color: Colors.white54),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({required this.icon, this.text});

  final IconData icon;
  final String? text;

  @override
  Widget build(BuildContext context) {
    if (text == null || text!.isEmpty) return const SizedBox.shrink();
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 15, color: AppColors.gold),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            text!,
            style: AppTypography.bodySmall(size: 11.5, color: Colors.white70),
          ),
        ),
      ],
    );
  }
}