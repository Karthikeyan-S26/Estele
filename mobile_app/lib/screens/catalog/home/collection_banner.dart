import 'package:flutter/material.dart';

import '../../../models/home_data.dart';
import '../../../theme/app_colors.dart';
import '../../../theme/app_typography.dart';
import '../../../utils/app_link.dart';
import '../../../widgets/app_image.dart';

/// The "Royal Rose Edition" collection banner — mirrors
/// `home/blocks/collection-banner.blade.php`:
///  - `section py-4`, content `px-3`;
///  - `rounded-xl bg-deepwine` card; mobile column: image 16/9 on top
///    (`order-1`), text block below (`order-2`);
///  - eyebrow `✶ subtitle` in gold, Playfair 26px title, white/70 body,
///    rose "Explore the collection" CTA with chevron.
class CollectionBannerBlock extends StatelessWidget {
  const CollectionBannerBlock({super.key, required this.banner});

  final CollectionBanner banner;

  @override
  Widget build(BuildContext context) {
    if (banner.image == null) {
      return const SizedBox.shrink();
    }

    final href = (banner.linkUrl != null && banner.linkUrl!.isNotEmpty)
        ? banner.linkUrl
        : (banner.collection != null && (banner.collection!.slug.isNotEmpty))
        ? '/collections/${banner.collection!.slug}'
        : null;

    final title = (banner.title.isNotEmpty ? banner.title : null) ??
        banner.collection?.name ??
        '';

    return Padding(
      // section py-4 + container px-3
      padding: const EdgeInsets.fromLTRB(12, 16, 12, 16),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(12), // rounded-xl
        child: Container(
          color: AppColors.deepWine,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // order-1: image block, 16/9 on mobile
              AspectRatio(
                aspectRatio: 16 / 9,
                child: AppImage(url: banner.image, fit: BoxFit.cover),
              ),
              // order-2: text block
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 24, 20, 24), // px-5 py-6
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (banner.subtitle != null &&
                        banner.subtitle!.isNotEmpty)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 8),
                        child: Text(
                          '✦ ${banner.subtitle}',
                          style: AppTypography.eyebrow().copyWith(
                            color: AppColors.gold, // !text-gold
                          ),
                        ),
                      ),
                    Text(
                      title,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: AppTypography.editorial(
                        size: 26, // text-[26px]
                        color: Colors.white,
                        weight: FontWeight.w600,
                        height: 1.25,
                      ),
                    ),
                    // mt-3 body — $item->body ?: $collection->description
                    if (banner.collection?.description case final body?
                        when body.isNotEmpty) ...[
                      Padding(
                        padding: const EdgeInsets.only(top: 12), // mt-3
                        child: Text(
                          body,
                          maxLines: 3,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            color: Colors.white70,
                            fontSize: 13,
                            height: 1.6,
                          ),
                        ),
                      ),
                    ],
                    if (href != null)
                      Padding(
                        padding: const EdgeInsets.only(top: 20), // mt-5
                        child: Material(
                          color: AppColors.accent, // bg-rose
                          borderRadius: BorderRadius.circular(6), // rounded-md
                          child: InkWell(
                            borderRadius: BorderRadius.circular(6),
                            onTap: () => resolveAppLink(context, href),
                            child: Padding(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 20,
                                vertical: 10, // py-2.5
                              ),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Text(
                                    'Explore the collection'
                                        .toUpperCase(), // 11px uppercase 0.14em
                                    style: AppTypography.button(
                                      size: 11,
                                      color: Colors.white,
                                      letterSpacing: 11 * 0.14,
                                    ),
                                  ),
                                  const SizedBox(width: 8),
                                  const Icon(
                                    Icons.arrow_forward,
                                    size: 14,
                                    color: Colors.white,
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
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