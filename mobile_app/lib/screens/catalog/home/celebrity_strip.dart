import 'package:flutter/material.dart';

import '../../../models/home_data.dart';
import '../../../theme/app_colors.dart';
import '../../../theme/app_typography.dart';
import '../../../widgets/app_image.dart';

/// "As Seen On → Celebrities" — the one dark section on the page. Mirrors
/// `home/blocks/celebrities.blade.php`:
///  - `section bg-deepwine py-8`, content `px-3`;
///  - centered heading: display "As Seen On" + script gold "Celebrities";
///  - `grid grid-cols-2 gap-2.5` of 1/1.3 portrait tiles (white/5 bg),
///    each with an uppercase 12px white/80 label.
class CelebrityStrip extends StatelessWidget {
  const CelebrityStrip({super.key, required this.celebrities, this.subtitle});

  final List<Celebrity> celebrities;
  final String? subtitle;

  @override
  Widget build(BuildContext context) {
    if (celebrities.isEmpty) return const SizedBox.shrink();

    return Container(
      color: AppColors.deepWine, // bg-deepwine
      padding: const EdgeInsets.symmetric(vertical: 32), // py-8
      width: double.infinity,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // mb-5 centered heading
          Padding(
            padding: const EdgeInsets.fromLTRB(12, 0, 12, 20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                Wrap(
                  alignment: WrapAlignment.center,
                  crossAxisAlignment: WrapCrossAlignment.center,
                  spacing: 12,
                  runSpacing: 4,
                  children: [
                    Text(
                      'As Seen On',
                      style: AppTypography.sectionTitle(
                        size: 19,
                        color: Colors.white,
                      ).copyWith(letterSpacing: 19 * 0.16),
                    ),
                    Text(
                      'Celebrities',
                      style: AppTypography.scriptAccent(
                        size: 32,
                        color: AppColors.gold,
                      ),
                    ),
                  ],
                ),
                if (subtitle != null && subtitle!.isNotEmpty)
                  ConstrainedBox(
                    constraints: const BoxConstraints(maxWidth: 420),
                    child: Padding(
                      padding: const EdgeInsets.only(top: 16), // mt-4
                      child: Text(
                        subtitle!,
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          color: Colors.white70,
                          fontSize: 13.5,
                          height: 1.6,
                        ),
                      ),
                    ),
                  ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12),
            child: GridView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: celebrities.length,
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2, // grid-cols-2
                crossAxisSpacing: 10, // gap-2.5
                mainAxisSpacing: 10,
                // aspect 1/1.3 tile + mt-3 (12px) + label line
                childAspectRatio: 1 / 1.3 * 0.78,
              ),
              itemBuilder: (context, i) {
                final celebrity = celebrities[i];
                return Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Expanded(
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(4),
                        child: Container(
                          color: Colors.white.withValues(alpha: 0.05),
                          child: AppImage(url: celebrity.image),
                        ),
                      ),
                    ),
                    const SizedBox(height: 12), // mt-3
                    Text(
                      celebrity.title,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.8),
                        fontSize: 12,
                        letterSpacing: 12 * 0.1, // tracking-[0.1em]
                      ),
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