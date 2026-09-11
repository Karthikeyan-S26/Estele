import 'package:flutter/material.dart';

import '../../../models/home_data.dart';
import '../../../theme/app_colors.dart';
import '../../../theme/app_typography.dart';
import '../../../utils/app_link.dart';

/// "Sparkle that stays… — The Estele Brand Story": dark full-width band with
/// stats + CTA, mirroring the web's brand-story section.
class StatsBlock extends StatelessWidget {
  const StatsBlock({super.key, required this.stats});

  final StatsSection stats;

  @override
  Widget build(BuildContext context) {
    if (stats.items.isEmpty) return const SizedBox.shrink();

    return Container(
      width: double.infinity,
      margin: const EdgeInsets.only(top: 28),
      padding: const EdgeInsets.fromLTRB(20, 28, 20, 28),
      color: AppColors.deepWine,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Text(
            'Estele',
            style: AppTypography.scriptAccent(size: 34, color: AppColors.goldLight),
          ),
          const SizedBox(height: 6),
          Container(
            width: 44,
            height: 2,
            color: AppColors.gold,
          ),
          const SizedBox(height: 14),
          Text(
            stats.title ?? 'Sparkle that stays, stories that shine',
            textAlign: TextAlign.center,
            style: AppTypography.sectionTitle(size: 20, color: Colors.white),
          ),
          if (stats.subtitle != null) ...[
            const SizedBox(height: 6),
            Text(
              stats.subtitle!,
              textAlign: TextAlign.center,
              style: AppTypography.bodySmall(size: 12, color: Colors.white70),
            ),
          ],
          const SizedBox(height: 20),
          GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: stats.items.length,
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 2,
              mainAxisSpacing: 14,
              crossAxisSpacing: 14,
              childAspectRatio: 2.4,
            ),
            itemBuilder: (context, i) {
              final stat = stats.items[i];
              return Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    stat.value,
                    maxLines: 1,
                    style: AppTypography.scriptAccent(size: 26, color: AppColors.goldLight),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    stat.label,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    textAlign: TextAlign.center,
                    style: AppTypography.bodySmall(size: 10.5, color: Colors.white70),
                  ),
                ],
              );
            },
          ),
          if (stats.ctaUrl != null) ...[
            const SizedBox(height: 16),
            OutlinedButton(
              onPressed: () => resolveAppLink(context, stats.ctaUrl),
              style: OutlinedButton.styleFrom(
                foregroundColor: AppColors.goldLight,
                side: const BorderSide(color: AppColors.gold),
                padding: const EdgeInsets.symmetric(horizontal: 22, vertical: 10),
              ),
              child: Text(
                stats.ctaLabel ?? 'Learn our story',
                style: AppTypography.label(size: 11),
              ),
            ),
          ],
        ],
      ),
    );
  }
}