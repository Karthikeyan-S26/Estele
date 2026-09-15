import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../data/repositories/catalog_repository.dart';
import '../../../models/home_data.dart';
import '../../../theme/app_colors.dart';
import '../../../theme/app_typography.dart';
import '../product_grid_screen.dart';
import '../../stores/stores_screen.dart';

/// Site footer (USP trust band, company info, contact, socials, popular
/// searches, payment badges) rendered at the bottom of the home list — mirrors
/// `partials/footer.blade.php`.
class FooterBlock extends StatelessWidget {
  const FooterBlock({
    super.key,
    required this.footer,
    this.services = const [],
  });

  final FooterData footer;

  /// The `footer_usps` trust strip rendered as the footer's top band.
  final List<ServiceBenefit> services;

  static const _uspIcons = <String, IconData>{
    'shield': Icons.verified_user_outlined,
    'return': Icons.autorenew_rounded,
    'truck': Icons.local_shipping_outlined,
    'lock': Icons.lock_outline_rounded,
  };

  void _openSearch(BuildContext context, String query) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => ProductGridScreen(
          title: 'Results for "$query"',
          autoLoad: true,
          loader:
              ({
                required String sort,
                String? minPrice,
                String? maxPrice,
                required bool inStock,
                required int page,
                required int perPage,
              }) {
                return CatalogRepository.search(
                  query,
                  sort: sort,
                  minPrice: minPrice,
                  maxPrice: maxPrice,
                  inStock: inStock,
                  page: page,
                  perPage: perPage,
                );
              },
        ),
      ),
    );
  }

  void _launch(String url) {
    final uri = Uri.tryParse(url);
    if (uri == null) return;
    launchUrl(
      uri,
      mode: LaunchMode.externalApplication,
    ).catchError((_) => false);
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      margin: const EdgeInsets.only(top: 28),
      color: AppColors.deepWine,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // USP trust band — the deepwine strip fused to the footer top
          // (`border-b border-white/10 bg-black/15`, 2-col grid on mobile).
          if (services.isNotEmpty)
            Container(
              width: double.infinity,
              decoration: const BoxDecoration(color: Colors.black26),
              padding: const EdgeInsets.fromLTRB(16, 24, 16, 24),
              child: GridView.count(
                crossAxisCount: 2,
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                mainAxisSpacing: 18,
                crossAxisSpacing: 12,
                childAspectRatio: 2.6,
                children: [
                  for (final service in services)
                    Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Container(
                          width: 40,
                          height: 40,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            border: Border.all(
                              color: AppColors.gold.withValues(alpha: 0.5),
                            ),
                          ),
                          child: Icon(
                            _uspIcons[service.icon] ??
                                Icons.check_circle_outline,
                            size: 20,
                            color: AppColors.gold,
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Text(
                                service.title ?? '',
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                                style: AppTypography.bodyMedium(
                                  size: 12.5,
                                  color: Colors.white,
                                  weight: FontWeight.w600,
                                ),
                              ),
                              if (service.body != null) ...[
                                const SizedBox(height: 1),
                                Text(
                                  service.body!,
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                  style: AppTypography.bodySmall(
                                    size: 11.5,
                                    color: Colors.white60,
                                  ),
                                ),
                              ],
                            ],
                          ),
                        ),
                      ],
                    ),
                ],
              ),
            ),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 24, 20, 28),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Estele',
                  style: AppTypography.scriptAccent(
                    size: 30,
                    color: AppColors.goldLight,
                  ),
                ),
                const SizedBox(height: 4),
                if (footer.companyName != null)
                  Text(
                    footer.companyName!,
                    style: AppTypography.bodySmall(
                      size: 11,
                      color: Colors.white70,
                    ),
                  ),
                const SizedBox(height: 12),
                if (footer.about != null) ...[
                  Text(
                    footer.about!,
                    style: AppTypography.bodySmall(
                      size: 11,
                      color: Colors.white70,
                    ).copyWith(height: 1.5),
                  ),
                  const SizedBox(height: 14),
                ],
                const Divider(color: AppColors.gold, thickness: 1),
                const SizedBox(height: 8),
                _InfoRow(
                  icon: Icons.phone_outlined,
                  text: footer.contactPhone,
                  onTap: () => _launch(
                    'tel:${(footer.contactPhone ?? '').replaceAll(RegExp(r'[\s-]'), '')}',
                  ),
                ),
                if (footer.contactPhone != null) const SizedBox(height: 8),
                _InfoRow(
                  icon: Icons.mail_outline,
                  text: footer.contactEmail,
                  onTap: () => _launch('mailto:${footer.contactEmail ?? ''}'),
                ),
                if (footer.contactEmail != null) const SizedBox(height: 8),
                _InfoRow(
                  icon: Icons.location_on_outlined,
                  text: footer.contactAddress,
                ),
                if (footer.contactAddress != null) const SizedBox(height: 8),
                _InfoRow(
                  icon: Icons.schedule_outlined,
                  text: footer.contactHours,
                ),
                if (footer.popularSearches.isNotEmpty) ...[
                  const SizedBox(height: 16),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: footer.popularSearches
                        .map(
                          (s) => GestureDetector(
                            onTap: () => _openSearch(context, s),
                            child: Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 12,
                                vertical: 6,
                              ),
                              decoration: BoxDecoration(
                                border: Border.all(
                                  color: AppColors.gold.withValues(alpha: 0.5),
                                ),
                                borderRadius: BorderRadius.circular(20),
                              ),
                              child: Text(
                                s,
                                style: AppTypography.bodySmall(
                                  size: 10.5,
                                  color: AppColors.goldLight,
                                ),
                              ),
                            ),
                          ),
                        )
                        .toList(),
                  ),
                ],
                const SizedBox(height: 12),
                Wrap(
                  spacing: 8,
                  children: [
                    GestureDetector(
                      onTap: () => Navigator.of(context).push(
                        MaterialPageRoute(builder: (_) => const StoresScreen()),
                      ),
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 6,
                        ),
                        decoration: BoxDecoration(
                          color: AppColors.gold.withValues(alpha: 0.15),
                          border: Border.all(
                            color: AppColors.gold.withValues(alpha: 0.5),
                          ),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Text(
                          'Our stores',
                          style: AppTypography.bodySmall(
                            size: 10.5,
                            color: AppColors.goldLight,
                            weight: FontWeight.w600,
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
                if (footer.paymentBadges.isNotEmpty) ...[
                  const SizedBox(height: 16),
                  Text(
                    'We accept',
                    style: AppTypography.label(size: 10, color: Colors.white70),
                  ),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: footer.paymentBadges
                        .map(
                          (p) => Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 10,
                              vertical: 5,
                            ),
                            decoration: BoxDecoration(
                              color: Colors.white.withValues(alpha: 0.08),
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: Text(
                              p,
                              style: AppTypography.bodySmall(
                                size: 9.5,
                                color: Colors.white,
                              ),
                            ),
                          ),
                        )
                        .toList(),
                  ),
                ],
                if (footer.copyright != null) ...[
                  const SizedBox(height: 20),
                  Center(
                    child: Text(
                      footer.copyright!,
                      textAlign: TextAlign.center,
                      style: AppTypography.bodySmall(
                        size: 10,
                        color: Colors.white54,
                      ),
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

class _InfoRow extends StatelessWidget {
  const _InfoRow({required this.icon, this.text, this.onTap});

  final IconData icon;
  final String? text;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    if (text == null || text!.isEmpty) return const SizedBox.shrink();
    return InkWell(
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 2),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(icon, size: 15, color: AppColors.gold),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                text!,
                style: AppTypography.bodySmall(
                  size: 11.5,
                  color: Colors.white70,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
