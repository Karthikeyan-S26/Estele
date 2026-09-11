import 'package:flutter/material.dart';

import '../../../models/home_data.dart';
import '../../../theme/app_colors.dart';
import '../../../theme/app_typography.dart';
import '../../../utils/app_link.dart';
import '../../../widgets/app_image.dart';

/// The full-bleed "Rose Gold Collection" banner under the category row —
/// artwork from the collection_banner CMS block, with a CTA into the
/// collection listing.
class CollectionBannerBlock extends StatelessWidget {
  const CollectionBannerBlock({super.key, required this.banner});

  final CollectionBanner banner;

  @override
  Widget build(BuildContext context) {
    if (banner.image == null) {
      return const SizedBox.shrink();
    }

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 24, 16, 0),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(4),
        child: SizedBox(
          height: 300,
          width: double.infinity,
          child: Stack(
            fit: StackFit.expand,
            children: [
              AppImage(url: banner.image),
              DecoratedBox(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [
                      Colors.transparent,
                      AppColors.deepWine.withValues(alpha: 0.82),
                    ],
                  ),
                ),
              ),
              Align(
                alignment: Alignment.bottomLeft,
                child: Padding(
                  padding: const EdgeInsets.all(18),
                  child: ConstrainedBox(
                    constraints: const BoxConstraints(maxWidth: 260),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          banner.title,
                          style: AppTypography.sectionTitle(
                            size: 22,
                            color: Colors.white,
                          ),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                        if (banner.subtitle != null) ...[
                          const SizedBox(height: 6),
                          Text(
                            banner.subtitle!,
                            style: AppTypography.bodySmall(size: 12, color: Colors.white70),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                        const SizedBox(height: 12),
                        OutlinedButton(
                          onPressed: () => resolveAppLink(context, banner.linkUrl),
                          style: OutlinedButton.styleFrom(
                            foregroundColor: Colors.white,
                            side: const BorderSide(color: AppColors.gold, width: 1),
                            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                          ),
                          child: Text('Explore the collection', style: AppTypography.label(size: 11)),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}