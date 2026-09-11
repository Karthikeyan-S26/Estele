import 'package:flutter/material.dart';

import '../../../models/home_data.dart';
import '../../../theme/app_colors.dart';
import '../../../theme/app_typography.dart';
import '../../../widgets/section_header.dart';

/// "5M+ Happy Customers": the testimonial carousel from the CMS block.
class TestimonialsBlock extends StatelessWidget {
  const TestimonialsBlock({super.key, required this.testimonials});

  final List<Testimonial> testimonials;

  @override
  Widget build(BuildContext context) {
    if (testimonials.isEmpty) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Padding(
          padding: EdgeInsets.fromLTRB(16, 28, 16, 4),
          child: SectionHeader(scriptWord: '', title: '5M+ Happy Customers'),
        ),
        const SizedBox(height: 12),
        SizedBox(
          height: 150,
          child: ListView.separated(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            scrollDirection: Axis.horizontal,
            itemCount: testimonials.length,
            separatorBuilder: (_, __) => const SizedBox(width: 12),
            itemBuilder: (context, i) {
              final testimonial = testimonials[i];
              final rating = testimonial.rating ?? 5;
              return Container(
                width: 228,
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: AppColors.ivory,
                  borderRadius: BorderRadius.circular(4),
                  border: Border.all(color: AppColors.line),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: List.generate(
                        5,
                        (s) => Icon(
                          s < rating ? Icons.star_rounded : Icons.star_outline_rounded,
                          size: 15,
                          color: AppColors.star,
                        ),
                      ),
                    ),
                    const SizedBox(height: 8),
                    Expanded(
                      child: Text(
                        (testimonial.body ?? '').trim(),
                        maxLines: 3,
                        overflow: TextOverflow.ellipsis,
                        style: AppTypography.body(size: 11.5),
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      testimonial.title,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: AppTypography.bodySmall(size: 11, color: AppColors.accentDark, weight: FontWeight.w600),
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