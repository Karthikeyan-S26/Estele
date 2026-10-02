import 'package:flutter/material.dart';

import '../../../models/home_data.dart';
import '../../../theme/app_colors.dart';
import '../../../theme/app_typography.dart';
import '../../../widgets/section_header.dart';

/// "Since 1989 → Why Indian Women Choose Estele" — mirrors
/// `home/blocks/usp.blade.php`:
///  - `border-y border-line bg-white py-6` section, content `px-3`;
///  - centered section-head (eyebrow → title → gold rule → subtitle);
///  - `grid grid-cols-2 gap-2.5` of USP cards: rounded-xl, border-line,
///    paper bg, centered; a 44px pink circle icon (or CMS image) above a
///    Playfair 14px title and 11.5px muted body.
class BenefitsBlock extends StatelessWidget {
  const BenefitsBlock({super.key, required this.benefits});

  final List<Benefit> benefits;

  @override
  Widget build(BuildContext context) {
    if (benefits.isEmpty) return const SizedBox.shrink();

    return Container(
      width: double.infinity,
      // border-y border-line bg-white py-6
      decoration: const BoxDecoration(
        color: AppColors.paper,
        border: Border(
          top: BorderSide(color: AppColors.line),
          bottom: BorderSide(color: AppColors.line),
        ),
      ),
      padding: const EdgeInsets.symmetric(vertical: 24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // .section-head mb-5
          const Padding(
            padding: EdgeInsets.fromLTRB(12, 0, 12, 20),
            child: SectionHeader(
              centered: true,
              scriptWord: 'Since 1989',
              title: 'Why Indian Women Choose Estele',
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12),
            // Natural-height 2-column rows (grid-cols-2, gap-2.5).
            child: Column(
              children: [
                for (var r = 0; r < (benefits.length / 2).ceil(); r++) ...[
                  if (r > 0) const SizedBox(height: 10),
                  IntrinsicHeight(
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Expanded(
                          child: _BenefitCard(benefit: benefits[r * 2]),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: r * 2 + 1 < benefits.length
                              ? _BenefitCard(benefit: benefits[r * 2 + 1])
                              : const SizedBox.shrink(),
                        ),
                      ],
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// Single USP card: rounded-xl, border-line, paper bg, centered — 44px pink
/// circle icon above a Playfair 14px title and 11.5px muted body. Sizes to
/// its own content (no fixed height), so text can never clip.
class _BenefitCard extends StatelessWidget {
  const _BenefitCard({required this.benefit});

  final Benefit benefit;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 16),
      decoration: BoxDecoration(
        color: AppColors.paper,
        borderRadius: BorderRadius.circular(12), // rounded-xl
        border: Border.all(color: AppColors.line),
      ),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          // h-11 w-11 rounded-full bg-pinksoft text-rose icon
          Container(
            width: 44,
            height: 44,
            decoration: const BoxDecoration(
              color: AppColors.pinkSoft,
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.diamond_outlined,
              size: 20, // h-5 w-5
              color: AppColors.accent,
            ),
          ),
          const SizedBox(height: 12), // mb-3
          Text(
            benefit.title,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            textAlign: TextAlign.center,
            style: AppTypography.editorial(
              size: 14, // font-serif text-[14px]
              color: AppColors.heading,
              weight: FontWeight.w600,
              height: 1.375, // leading-snug
            ),
          ),
          if (benefit.body != null && benefit.body!.isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(top: 6), // mt-1.5
              child: Text(
                benefit.body!,
                maxLines: 3,
                overflow: TextOverflow.ellipsis,
                textAlign: TextAlign.center,
                style: AppTypography.body(
                  size: 11.5,
                  color: AppColors.muted,
                ),
              ),
            ),
        ],
      ),
    );
  }
}