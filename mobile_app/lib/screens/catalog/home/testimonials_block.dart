import 'package:flutter/material.dart';

import '../../../models/home_data.dart';
import '../../../theme/app_colors.dart';
import '../../../theme/app_typography.dart';
import '../../../widgets/section_header.dart';

/// "5M+ Happy Customers" — mirrors `home/blocks/testimonials.blade.php`:
///  - `section bg-pinksoft py-6`, content `px-3`;
///  - centered section-head (title → gold rule → subtitle);
///  - horizontal carousel, cards `flex-[0_0_88%]` (88% of viewport) with
///    `border border-line bg-paper`: 13px star row, 12.5px quote, `– name` cite.
class TestimonialsBlock extends StatelessWidget {
  const TestimonialsBlock({super.key, required this.testimonials});

  final List<Testimonial> testimonials;

  @override
  Widget build(BuildContext context) {
    if (testimonials.isEmpty) return const SizedBox.shrink();

    return Container(
      color: AppColors.pinkSoft, // bg-pinksoft
      padding: const EdgeInsets.symmetric(vertical: 24), // py-6
      width: double.infinity,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // .section-head mb-5
          const Padding(
            padding: EdgeInsets.fromLTRB(12, 0, 12, 20),
            child: SectionHeader(
              centered: true,
              scriptWord: '',
              title: '5M+ Happy Customers',
            ),
          ),
          SizedBox(
            height: 172,
            child: ListView.separated(
              padding: const EdgeInsets.symmetric(horizontal: 12),
              scrollDirection: Axis.horizontal,
              itemCount: testimonials.length,
              separatorBuilder: (_, __) => const SizedBox(width: 14), // gap-3.5
              itemBuilder: (context, i) {
                final testimonial = testimonials[i];
                final rating = testimonial.rating ?? 5;
                return SizedBox(
                  // flex-[0_0_88%] on mobile
                  width: MediaQuery.sizeOf(context).width * 0.88 - 12,
                  child: Container(
                    decoration: BoxDecoration(
                      color: AppColors.paper, // bg-paper
                      borderRadius: BorderRadius.circular(4),
                      border: Border.all(color: AppColors.line), // border-line
                    ),
                    padding: const EdgeInsets.all(16), // p-4
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // 13px star row
                        Row(
                          children: List.generate(
                            5,
                            (s) => Icon(
                              s < rating
                                  ? Icons.star_rounded
                                  : Icons.star_outline_rounded,
                              size: 15,
                              color: s < rating
                                  ? AppColors.star
                                  : AppColors.lineStrong,
                            ),
                          ),
                        ),
                        const SizedBox(height: 10), // mb-2.5
                        Expanded(
                          child: Text(
                            (testimonial.body ?? '').trim(),
                            maxLines: 4,
                            overflow: TextOverflow.ellipsis,
                            style: AppTypography.body(
                              size: 12.5,
                              color: AppColors.ink,
                            ),
                          ),
                        ),
                        const SizedBox(height: 14), // mb-3.5
                        Text(
                          '- ${testimonial.title}',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: AppTypography.bodySmall(
                            size: 12,
                            color: AppColors.muted,
                          ),
                        ),
                      ],
                    ),
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