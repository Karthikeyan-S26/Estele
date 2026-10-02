import 'package:flutter/material.dart';

import '../../../models/home_data.dart';
import '../../../theme/app_colors.dart';
import '../../../theme/app_typography.dart';
import '../../../utils/app_link.dart';

/// "Sparkle that stays… — The Estele Brand Story" — mirrors
/// `home/blocks/brand-story.blade.php`:
///  - `section bg-ivory py-8`, content `px-3`;
///  - gold-leaf "Estele" wordmark + script rose title + muted subtitle;
///  - dark `bg-heading` CTA;
///  - 2-col grid of white stat cards (`rounded-xl border-line bg-white`):
///    Cinzel 26px value + 11px uppercase muted label.
class StatsBlock extends StatelessWidget {
  const StatsBlock({super.key, required this.stats});

  final StatsSection stats;

  @override
  Widget build(BuildContext context) {
    if (stats.items.isEmpty) return const SizedBox.shrink();

    return Container(
      color: AppColors.ivory, // bg-ivory
      padding: const EdgeInsets.symmetric(vertical: 32), // py-8
      width: double.infinity,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          AppTypography.goldLeafLogo(fontSize: 28),
          if (stats.title != null && stats.title!.isNotEmpty) ...[
            const SizedBox(height: 8),
            Text(
              stats.title!, // script rose
              textAlign: TextAlign.center,
              style: AppTypography.scriptAccent(
                size: 26,
                color: AppColors.accent,
              ),
            ),
          ],
          if (stats.subtitle != null && stats.subtitle!.isNotEmpty) ...[
            const SizedBox(height: 16), // mt-5
            ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 560),
              child: Text(
                stats.subtitle!,
                textAlign: TextAlign.center,
                style: AppTypography.body(
                  size: 13.5,
                  color: AppColors.muted,
                  height: 1.9,
                ),
              ),
            ),
          ],
          if (stats.ctaUrl != null) ...[
            const SizedBox(height: 20), // mt-5
            Material(
              color: AppColors.heading, // bg-heading
              borderRadius: BorderRadius.circular(6), // rounded-md
              child: InkWell(
                borderRadius: BorderRadius.circular(6),
                onTap: () => resolveAppLink(context, stats.ctaUrl),
                child: Padding(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 20, // px-5
                    vertical: 10, // py-2.5
                  ),
                  child: Text(
                    (stats.ctaLabel ?? 'Learn our story').toUpperCase(),
                    style: AppTypography.button(
                      size: 11, // text-[11px] 0.14em
                      color: Colors.white,
                      letterSpacing: 11 * 0.14,
                    ),
                  ),
                ),
              ),
            ),
          ],
          const SizedBox(height: 24),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12),
            child: GridView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: stats.items.length,
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2, // grid-cols-2
                crossAxisSpacing: 12, // gap-3
                mainAxisSpacing: 12,
                childAspectRatio: 1.35,
              ),
              itemBuilder: (context, i) {
                final stat = stats.items[i];
                return Container(
                  // rounded-xl border-line bg-white px-4 py-6 text-center
                  padding: const EdgeInsets.symmetric(
                    horizontal: 16,
                    vertical: 24,
                  ),
                  decoration: BoxDecoration(
                    color: AppColors.paper,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: AppColors.line),
                  ),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        stat.value,
                        maxLines: 1,
                        style: AppTypography.sectionTitle(
                          size: 26, // font-display 26px
                          color: AppColors.heading,
                        ),
                      ),
                      const SizedBox(height: 8), // mt-2
                      Text(
                        stat.label,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        textAlign: TextAlign.center,
                        style: AppTypography.bodySmall(
                          size: 11, // text-[11px]
                          color: AppColors.muted,
                          weight: FontWeight.w500,
                        ).copyWith(letterSpacing: 11 * 0.14),
                      ),
                    ],
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}