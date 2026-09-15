import 'package:flutter/material.dart';

import '../../../data/repositories/catalog_repository.dart';
import '../../../models/home_data.dart';
import '../../../theme/app_colors.dart';
import '../../../theme/app_typography.dart';
import '../../../utils/app_link.dart';
import '../product_grid_screen.dart';

/// "Every price, same sparkle → Your Budget, Your Bling" — mirrors
/// `home/blocks/price-tiers.blade.php`:
///  - `border-y border-line bg-white py-6` section, content `px-3`;
///  - left column: eyebrow + Playfair 22px two-line title;
///  - right: mobile `grid grid-cols-2 gap-2.5` of tile cards — rounded-xl,
///    border-line; last tile premium (`bg-deepwine text-white`) and the rest
///    `bg-gradient-to-br from-pinksoft to-white`;
///  - label 10.5px semibold uppercase tracking-[0.18em] (gold on premium),
///    Playfair 24px amount, gold chevron circle;
///  - tapping a tile opens a real price-filtered search grid.
class BudgetTiles extends StatelessWidget {
  const BudgetTiles({super.key, required this.tiers});

  final List<PriceTier> tiers;

  void _openTier(BuildContext context, PriceTier tier) {
    final hasRange = tier.minPrice != null || tier.maxPrice != null;
    if (!hasRange) {
      resolveAppLink(context, '/search');
      return;
    }

    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => ProductGridScreen(
          title: tier.label,
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
                  '',
                  sort: sort,
                  minPrice: minPrice ?? tier.minPrice?.toString(),
                  maxPrice: maxPrice ?? tier.maxPrice?.toString(),
                  inStock: inStock,
                  page: page,
                  perPage: perPage,
                );
              },
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (tiers.isEmpty) return const SizedBox.shrink();

    return Container(
      // border-y border-line bg-white py-6
      decoration: const BoxDecoration(
        color: AppColors.paper,
        border: Border(
          top: BorderSide(color: AppColors.line),
          bottom: BorderSide(color: AppColors.line),
        ),
      ),
      padding: const EdgeInsets.symmetric(vertical: 24),
      width: double.infinity,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(12, 0, 12, 16),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Every price, same sparkle'.toUpperCase(),
                        style: AppTypography.eyebrow(),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        'Your Budget,\nYour Bling',
                        style: AppTypography.editorial(
                          size: 22,
                          height: 1.25,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12),
            child: LayoutBuilder(
              builder: (context, constraints) {
                final tileWidth =
                    (constraints.maxWidth - 10) / 2; // 2 cols, gap-2.5
                final tileHeight = tileWidth * 1.05;
                return Wrap(
                  spacing: 10,
                  runSpacing: 10,
                  children: [
                    for (var i = 0; i < tiers.length; i++)
                      _BudgetTile(
                        tier: tiers[i],
                        premium: i == tiers.length - 1, // $loop->last
                        height: tileHeight,
                        onTap: () => _openTier(context, tiers[i]),
                      ),
                  ],
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

class _BudgetTile extends StatelessWidget {
  const _BudgetTile({
    required this.tier,
    required this.premium,
    required this.height,
    required this.onTap,
  });

  final PriceTier tier;
  final bool premium;
  final double height;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: (MediaQuery.sizeOf(context).width - 24 - 10) / 2,
      height: height,
      child: Material(
        color: premium ? AppColors.deepWine : AppColors.pinkSoft,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12), // rounded-xl
          side: const BorderSide(color: AppColors.line), // border-line
        ),
        child: InkWell(
          borderRadius: BorderRadius.circular(12),
          onTap: onTap,
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 16),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                // label — uppercase 10.5 semibold, tracking .18em
                Text(
                  tier.label,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  textAlign: TextAlign.center,
                  style: AppTypography.bodySmall(
                    size: 10.5,
                    color: premium ? AppColors.gold : AppColors.muted,
                    weight: FontWeight.w600,
                  ).copyWith(letterSpacing: 10.5 * 0.18),
                ),
                const SizedBox(height: 4), // mt-1
                // amount — Playfair 24 semibold
                Text(
                  tier.amount,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  textAlign: TextAlign.center,
                  style: AppTypography.editorial(
                    size: 24,
                    color: premium ? Colors.white : AppColors.heading,
                    weight: FontWeight.w600,
                    height: 1,
                  ),
                ),
                const SizedBox(height: 12), // mt-3
                // chevron circle — 24px gold
                Container(
                  width: 24,
                  height: 24,
                  decoration: BoxDecoration(
                    color: premium
                        ? Colors.white.withValues(alpha: 0.2)
                        : AppColors.gold,
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(
                    Icons.chevron_right,
                    size: 12,
                    color: Colors.white,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}