import 'package:flutter/material.dart';

import '../../../models/home_data.dart';
import '../../../theme/app_colors.dart';
import '../../../theme/app_typography.dart';
import '../../../utils/app_link.dart';
import '../../../widgets/app_image.dart';
import '../../../widgets/section_header.dart';

/// "Style Notes → From the Journal": horizontal blog cards plus a promotional
/// tile, from the journal CMS block.
class JournalBlock extends StatelessWidget {
  const JournalBlock({super.key, required this.journal});

  final JournalSection journal;

  @override
  Widget build(BuildContext context) {
    final items = journal.items;
    if (items.isEmpty) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 28, 16, 4),
          child: SectionHeader(
            scriptWord: 'Style Notes',
            title: 'From the Journal',
            onViewAll: journal.ctaUrl == null ? null : () => resolveAppLink(context, journal.ctaUrl),
            viewAllLabel: 'All stories',
          ),
        ),
        const SizedBox(height: 12),
        SizedBox(
          height: 224,
          child: ListView.separated(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            scrollDirection: Axis.horizontal,
            itemCount: items.length,
            separatorBuilder: (_, __) => const SizedBox(width: 12),
            itemBuilder: (context, i) {
              final item = items[i];
              return _JournalItemCard(item: item);
            },
          ),
        ),
      ],
    );
  }
}

class _JournalItemCard extends StatelessWidget {
  const _JournalItemCard({required this.item});

  final JournalItem item;

  @override
  Widget build(BuildContext context) {
    final isPromo = item.type == 'promo';
    final metaText = [item.category, item.author].whereType<String>().join(' · ');

    return InkWell(
      borderRadius: BorderRadius.circular(4),
      onTap: item.linkUrl == null ? null : () => resolveAppLink(context, item.linkUrl),
      child: SizedBox(
        width: 244,
        child: isPromo
            ? Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: AppColors.deepWine,
                  borderRadius: BorderRadius.circular(4),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.campaign_outlined, size: 20, color: AppColors.gold),
                    const SizedBox(height: 8),
                    Text(
                      item.title ?? '',
                      style: AppTypography.sectionTitle(size: 16, color: Colors.white),
                      maxLines: 3,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 6),
                    Text(
                      item.body ?? item.body ?? '',
                      style: AppTypography.bodySmall(size: 11, color: Colors.white70),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ),
              )
            : Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    height: 156,
                    width: double.infinity,
                    clipBehavior: Clip.antiAlias,
                    decoration: BoxDecoration(
                      color: AppColors.warmBeige,
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: AppImage(url: item.image),
                  ),
                  const SizedBox(height: 8),
                  if (metaText.isNotEmpty)
                    Text(
                      metaText,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: AppTypography.label(size: 9, color: AppColors.accentDark),
                    ),
                  const SizedBox(height: 2),
                  Text(
                    item.title ?? '',
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: AppTypography.bodyMedium(size: 12.5, color: AppColors.heading, weight: FontWeight.w600),
                  ),
                ],
              ),
      ),
    );
  }
}