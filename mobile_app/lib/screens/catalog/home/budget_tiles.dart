import 'package:flutter/material.dart';

import '../../../models/home_data.dart';
import '../../../theme/app_colors.dart';
import '../../../theme/app_typography.dart';
import '../../../utils/app_link.dart';
import '../../../widgets/section_header.dart';

/// "Every price, same sparkle → Your Budget, Your Bling": tappable budget
/// tiles that route into search.
class BudgetTiles extends StatelessWidget {
  const BudgetTiles({super.key, required this.tiers});

  final List<PriceTier> tiers;

  @override
  Widget build(BuildContext context) {
    if (tiers.isEmpty) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Padding(
          padding: EdgeInsets.fromLTRB(16, 24, 16, 8),
          child: SectionHeader(scriptWord: 'Every price, same sparkle', title: 'Your Budget,\nYour Bling'),
        ),
        SizedBox(
          height: 96,
          child: ListView.separated(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            scrollDirection: Axis.horizontal,
            itemCount: tiers.length,
            separatorBuilder: (_, __) => const SizedBox(width: 10),
            itemBuilder: (context, i) {
              final tier = tiers[i];
              return InkWell(
                borderRadius: BorderRadius.circular(4),
                onTap: () => resolveAppLink(context, '/search'),
                child: Container(
                  width: 104,
                  decoration: BoxDecoration(
                    color: AppColors.pinkSoft,
                    borderRadius: BorderRadius.circular(4),
                    border: Border.all(color: AppColors.line),
                  ),
                  padding: const EdgeInsets.all(10),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        tier.label,
                        style: AppTypography.bodySmall(
                          size: 10.5,
                          color: AppColors.accentDark,
                          weight: FontWeight.w700,
                        ).copyWith(letterSpacing: 1.1),
                      ),
                      const SizedBox(height: 3),
                      Text(
                        tier.amount,
                        style: AppTypography.sectionTitle(size: 14, color: AppColors.heading),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
        ),
      ],
    );
  }
}