import 'package:flutter/material.dart';

import '../../../models/home_data.dart';
import '../../../theme/app_colors.dart';
import '../../../theme/app_typography.dart';
import '../../../widgets/section_header.dart';

/// "Since 1989 → Why Indian Women Choose Estele": the USPs from the CMS block
/// rendered as a 2-column card grid.
class BenefitsBlock extends StatelessWidget {
  const BenefitsBlock({super.key, required this.benefits});

  final List<Benefit> benefits;

  @override
  Widget build(BuildContext context) {
    if (benefits.isEmpty) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Padding(
          padding: EdgeInsets.fromLTRB(16, 28, 16, 4),
          child: SectionHeader(scriptWord: 'Since 1989', title: 'Why Indian Women Choose Estele'),
        ),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          child: GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: benefits.length,
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 2,
              mainAxisSpacing: 10,
              crossAxisSpacing: 10,
              childAspectRatio: 1.5,
            ),
            itemBuilder: (context, i) {
              final benefit = benefits[i];
              return Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppColors.ivory,
                  borderRadius: BorderRadius.circular(4),
                  border: Border.all(color: AppColors.line),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.diamond_outlined, size: 16, color: AppColors.gold),
                    const SizedBox(height: 8),
                    Text(
                      benefit.title,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: AppTypography.bodyMedium(size: 12, color: AppColors.heading),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      benefit.body ?? '',
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: AppTypography.bodySmall(size: 10.5),
                    ),
                  ],
                ),
              );
            },
          ),
        ),
      ],
    );
  }
}