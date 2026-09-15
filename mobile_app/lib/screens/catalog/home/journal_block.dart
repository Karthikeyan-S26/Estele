import 'package:flutter/material.dart';

import '../../../models/home_data.dart';
import '../../../theme/app_colors.dart';
import '../../../theme/app_typography.dart';
import '../../../utils/app_link.dart';
import '../../../widgets/app_image.dart';
import '../../../widgets/section_header.dart';

/// "Style Notes → From the Journal" — mirrors `home/blocks/journal.blade.php`:
///  - `section bg-white py-7`, content `px-3`;
///  - left-aligned section-head (eyebrow → title + "All stories" CTA);
///  - single column: first 2 blog cards (4/3 image, category · date, title,
///    2-line excerpt) + the "Care Guide" promo deepwine card.
class JournalBlock extends StatelessWidget {
  const JournalBlock({super.key, required this.journal});

  final JournalSection journal;

  @override
  Widget build(BuildContext context) {
    final blogs =
        journal.items.where((e) => e.type == 'blog').take(2).toList();
    final promo =
        journal.items.where((e) => e.type == 'promo').firstOrNull;
    if (blogs.isEmpty && promo == null) return const SizedBox.shrink();

    return Container(
      // section bg-white py-7
      color: AppColors.paper,
      padding: const EdgeInsets.symmetric(vertical: 28),
      width: double.infinity,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // align=left header, mb-4
          Padding(
            padding: const EdgeInsets.fromLTRB(12, 0, 12, 16),
            child: SectionHeader(
              scriptWord: 'Style Notes',
              title: 'From the Journal',
              onViewAll: journal.ctaUrl == null
                  ? null
                  : () => resolveAppLink(context, journal.ctaUrl),
              viewAllLabel: 'All stories',
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                for (final blog in blogs) _BlogCard(blog: blog),
                if (promo != null) ...[
                  const SizedBox(height: 16),
                  _PromoCard(item: promo),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// Formats like the web's `->format('M j, Y')`.
String _formatWebDate(DateTime date) {
  const months = [
    'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
  ];
  return '${months[date.month - 1]} ${date.day}, ${date.year}';
}

class _BlogCard extends StatelessWidget {
  const _BlogCard({required this.blog});

  final JournalItem blog;

  @override
  Widget build(BuildContext context) {
    final metaText = [
      blog.category,
      if (blog.publishedAt != null) _formatWebDate(blog.publishedAt!),
    ].whereType<String>().where((s) => s.isNotEmpty).join(' · ');

    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: InkWell(
        borderRadius: BorderRadius.circular(8),
        onTap: blog.linkUrl == null
            ? null
            : () => resolveAppLink(context, blog.linkUrl),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // aspect 4/3 rounded-lg bg-placeholder
            ClipRRect(
              borderRadius: BorderRadius.circular(8),
              child: AspectRatio(
                aspectRatio: 4 / 3,
                child: AppImage(
                  url: blog.image,
                  placeholderColor: AppColors.greySoft,
                ),
              ),
            ),
            if (metaText.isNotEmpty)
              Padding(
                padding: const EdgeInsets.only(top: 12), // mt-3
                child: Text(
                  metaText,
                  style: AppTypography.bodySmall(
                    size: 11,
                    color: AppColors.muted,
                  ).copyWith(letterSpacing: 0.3, height: 1),
                ),
              ),
            const SizedBox(height: 4), // mt-1
            Text(
              blog.title ?? '',
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: AppTypography.bodyMedium(
                size: 15,
                color: AppColors.heading,
                weight: FontWeight.w500,
              ),
            ),
            if (blog.body != null && blog.body!.isNotEmpty)
              Padding(
                padding: const EdgeInsets.only(top: 4), // mt-1
                child: Text(
                  blog.body!,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: AppTypography.body(
                    size: 13,
                    color: AppColors.muted,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

/// The deepwine "Care Guide" promo tile — `rounded-lg bg-deepwine p-6`,
/// gold eyebrow + Playfair title + body + gold "Read the guide" CTA.
class _PromoCard extends StatelessWidget {
  const _PromoCard({required this.item});

  final JournalItem item;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: AppColors.deepWine,
      borderRadius: BorderRadius.circular(8),
      child: InkWell(
        borderRadius: BorderRadius.circular(8),
        onTap: item.linkUrl == null
            ? null
            : () => resolveAppLink(context, item.linkUrl),
        child: Padding(
          padding: const EdgeInsets.all(24), // p-6
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'CARE GUIDE',
                style: AppTypography.eyebrow().copyWith(color: AppColors.gold),
              ),
              const SizedBox(height: 8),
              Text(
                item.title ?? '',
                maxLines: 3,
                overflow: TextOverflow.ellipsis,
                style: AppTypography.editorial(
                  size: 20,
                  color: Colors.white,
                  weight: FontWeight.w600,
                  height: 1.3,
                ),
              ),
              if (item.body != null && item.body!.isNotEmpty)
                Padding(
                  padding: const EdgeInsets.only(top: 12), // mt-3
                  child: Text(
                    item.body!,
                    maxLines: 3,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      color: Colors.white70,
                      fontSize: 12.5,
                      height: 1.6,
                    ),
                  ),
                ),
              Padding(
                padding: const EdgeInsets.only(top: 16), // mt-4
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      'Read the guide',
                      style: AppTypography.button(
                        size: 11,
                        color: AppColors.gold,
                        letterSpacing: 11 * 0.14,
                      ),
                    ),
                    const SizedBox(width: 6),
                    const Icon(
                      Icons.arrow_forward,
                      size: 14,
                      color: AppColors.gold,
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}