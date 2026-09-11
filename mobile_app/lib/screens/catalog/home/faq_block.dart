import 'package:flutter/material.dart';

import '../../../models/faq_item.dart';
import '../../../theme/app_colors.dart';
import '../../../theme/app_typography.dart';
import '../../../widgets/section_header.dart';

/// "Help Centre → Questions? Answered.": FAQ accordion from the CMS faqs data.
class FaqBlock extends StatelessWidget {
  const FaqBlock({super.key, required this.faqs});

  final List<FaqItem> faqs;

  @override
  Widget build(BuildContext context) {
    if (faqs.isEmpty) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 28, 16, 4),
          child: SectionHeader(
            scriptWord: 'Help Centre',
            title: 'Questions? Answered.',
            onViewAll: () => Navigator.of(context).pushNamed('/faq'),
            viewAllLabel: 'All FAQs',
          ),
        ),
        Container(
          margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          decoration: BoxDecoration(
            color: AppColors.ivory,
            borderRadius: BorderRadius.circular(4),
            border: Border.all(color: AppColors.line),
          ),
          child: Column(
            children: List.generate(
              faqs.length,
              (i) {
                final faq = faqs[i];
                return ExpansionTile(
                  key: ValueKey('faq-$i'),
                  tilePadding: const EdgeInsets.symmetric(horizontal: 14),
                  childrenPadding: const EdgeInsets.fromLTRB(14, 0, 14, 12),
                  iconColor: AppColors.accent,
                  collapsedIconColor: AppColors.accent,
                  title: Text(
                    faq.question,
                    style: AppTypography.bodyMedium(size: 12.5, color: AppColors.heading, weight: FontWeight.w600),
                  ),
                  children: [
                    Align(
                      alignment: Alignment.centerLeft,
                      child: Text(
                        faq.answer,
                        style: AppTypography.bodySmall(size: 11.5),
                      ),
                    ),
                  ],
                );
              },
            ),
          ),
        ),
      ],
    );
  }
}