import 'package:flutter/material.dart';
import 'package:shimmer/shimmer.dart';

import '../theme/app_colors.dart';
import '../theme/app_typography.dart';

/// Skeleton while the home screen loads — flat shimmer cards on ivory.
class HomeShimmer extends StatelessWidget {
  const HomeShimmer({super.key});

  @override
  Widget build(BuildContext context) {
    return Shimmer.fromColors(
      baseColor: AppColors.warmBeige,
      highlightColor: AppColors.line,
      child: ListView(
        physics: const NeverScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        children: [
          // hero
          Container(
            height: 180,
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(4),
            ),
          ),
          const SizedBox(height: 24),
          _row(),
          const SizedBox(height: 16),
          _gridPreview(),
        ],
      ),
    );
  }

  Widget _row() => Row(
    children: [
      Container(
        width: 140,
        height: 18,
        decoration: const BoxDecoration(color: Colors.white),
      ),
      const SizedBox(width: 10),
      Container(
        width: 90,
        height: 18,
        decoration: const BoxDecoration(color: Colors.white),
      ),
    ],
  );

  Widget _gridPreview() => SingleChildScrollView(
    scrollDirection: Axis.horizontal,
    physics: const NeverScrollableScrollPhysics(),
    child: Row(
      children: List.generate(
        3,
        (_) => Container(
          margin: const EdgeInsets.only(right: 12),
          width: 150,
          height: 220,
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(4),
          ),
        ),
      ),
    ),
  );
}

enum LoadStateType { loading, error, empty, onboarding }

/// A generic loading/empty/error state for list screens.
class LoadState extends StatelessWidget {
  const LoadState.error({super.key, required this.message, this.onRetry})
    : type = LoadStateType.error,
      emphasis = null,
      icon = null,
      action = null;

  const LoadState.empty({super.key, required this.message})
    : type = LoadStateType.empty,
      emphasis = null,
      onRetry = null,
      icon = null,
      action = null;

  const LoadState.loading({super.key})
    : message = '',
      type = LoadStateType.loading,
      emphasis = null,
      onRetry = null,
      icon = null,
      action = null;

  const LoadState.onboarding({
    super.key,
    required this.message,
    required this.emphasis,
    required this.icon,
  }) : type = LoadStateType.onboarding,
       onRetry = null,
       action = null;

  final LoadStateType type;
  final String message;
  final String? emphasis;
  final IconData? icon;
  final void Function()? onRetry;
  final Widget? action;

  @override
  Widget build(BuildContext context) {
    switch (type) {
      case LoadStateType.loading:
        return const Center(child: CircularProgressIndicator(strokeWidth: 2.4));
      case LoadStateType.error:
        return _Block(
          icon: Icons.wifi_off_rounded,
          title: 'Could not load',
          message: message,
          action: onRetry != null
              ? FilledButton.icon(
                  onPressed: onRetry,
                  icon: const Icon(Icons.refresh, size: 18),
                  label: const Text('Retry'),
                )
              : null,
        );
      case LoadStateType.empty:
        return _Block(
          icon: Icons.inbox_outlined,
          title: 'Nothing here',
          message: message,
        );
      case LoadStateType.onboarding:
        return _Block(
          icon: icon ?? Icons.workspace_premium_outlined,
          title: message,
          message: emphasis ?? '',
        );
    }
  }
}

class _Block extends StatelessWidget {
  const _Block({
    required this.icon,
    required this.title,
    required this.message,
    this.action,
  });

  final IconData icon;
  final String title;
  final String message;
  final Widget? action;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 44, color: AppColors.lineStrong),
            const SizedBox(height: 12),
            Text(
              title,
              style: AppTypography.sectionTitle(size: 17),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 6),
            Text(
              message,
              textAlign: TextAlign.center,
              style: AppTypography.body(size: 13.5, color: AppColors.muted),
            ),
            if (action != null) ...[const SizedBox(height: 16), action!],
          ],
        ),
      ),
    );
  }
}
