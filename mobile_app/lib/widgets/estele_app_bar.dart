import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../providers/auth_provider.dart';
import '../providers/cart_provider.dart';
import '../providers/wishlist_provider.dart';
import '../theme/app_colors.dart';
import '../theme/app_typography.dart';
import 'brand_icons.dart';

/// The single shared Estele mobile header — hamburger · ESTELE wordmark ·
/// search / wishlist / cart / account icons plus the always-visible "Search
/// for products" pill. Extracted verbatim from the app shell (RootScreen) so
/// every customer-facing screen renders the identical header from one source:
/// same background, logo, icons, actions, spacing, typography and colors.
///
/// A Scaffold `appBar:` is pinned by the framework — content scrolls
/// underneath it, it never scrolls away, and nothing can hide below it.
///
/// `onMenuTap` selects the leading control: the shell passes its drawer
/// opener (hamburger); pushed screens pass null and get the standard back
/// button instead. All other callbacks are required and identical everywhere.
class EsteleAppBar extends StatelessWidget implements PreferredSizeWidget {
  const EsteleAppBar({
    super.key,
    required this.onMenuTap,
    required this.onLogoTap,
    required this.onSearchTap,
    required this.onWishlistTap,
    required this.onAccountTap,
  });

  /// Hamburger opens the shell drawer; null shows the platform back button.
  final VoidCallback? onMenuTap;
  final VoidCallback onLogoTap;
  final VoidCallback onSearchTap;
  final VoidCallback onWishlistTap;
  final VoidCallback onAccountTap;

  static const _totalHeight = 56.0 + 52.0;

  @override
  Size get preferredSize => const Size.fromHeight(_totalHeight);

  @override
  Widget build(BuildContext context) {
    final cartCount = context.select<CartProvider, int>(
      (c) => c.cart.cartCount,
    );
    final wishlistCount = context.select<WishlistProvider, int>((w) => w.count);

    // .header-gradient — ivory AppBar with a 1px border-line bottom border
    // applied under the whole header (wordmark row AND search pill).
    return AppBar(
      toolbarHeight: 56,
      // Hamburger — 20px icon in a 38px touch target (web mobile header).
      // Null on pushed screens: AppBar then implies the back button instead.
      leading: onMenuTap == null
          ? null
          : IconButton(
              key: const ValueKey('menu-button'),
              icon: const BrandIcon(
                icon: BrandIconName.menu,
                size: 20,
                strokeWidth: 1.6,
                color: AppColors.heading,
              ),
              onPressed: onMenuTap,
              // Web header uses an aria-label, not a hover tooltip bubble — and
              // a Tooltip here leaks tickers when the shell is tapped repeatedly.
              iconSize: 20,
              padding: EdgeInsets.zero,
              constraints: const BoxConstraints.tightFor(width: 38, height: 38),
            ),
      title: GestureDetector(
        onTap: onLogoTap,
        child: AppTypography.logo(fontSize: 21),
      ),
      // Search · Wishlist · Cart · Account (same order as the website).
      actions: [
        _HeaderIcon(
          onTap: onSearchTap,
          child: const BrandIcon(
            icon: BrandIconName.search,
            size: 20,
            strokeWidth: 1.6,
            color: AppColors.heading,
          ),
        ),
        _HeaderIcon(
          onTap: onWishlistTap,
          child: _BadgedIcon(
            count: wishlistCount,
            icon: const BrandIcon(
              icon: BrandIconName.heart,
              size: 20,
              strokeWidth: 1.6,
              color: AppColors.heading,
            ),
          ),
        ),
        _HeaderIcon(
          onTap: () => Navigator.of(context).pushNamed('/cart'),
          child: _BadgedIcon(
            count: cartCount,
            icon: const BrandIcon(
              icon: BrandIconName.bag,
              size: 20,
              strokeWidth: 1.6,
              color: AppColors.heading,
            ),
          ),
        ),
        _HeaderIcon(
          onTap: onAccountTap,
          child: const BrandIcon(
            icon: BrandIconName.user,
            size: 20,
            strokeWidth: 1.6,
            color: AppColors.heading,
          ),
        ),
        const SizedBox(width: 8),
      ],
      // Always-visible mobile search pill (web `md:hidden` search form),
      // with the header-gradient 1px border-line at the header's bottom.
      bottom: PreferredSize(
        preferredSize: const Size.fromHeight(52),
        child: Container(
          decoration: const BoxDecoration(
            border: Border(bottom: BorderSide(color: AppColors.line)),
          ),
          child: Padding(
            padding: const EdgeInsets.fromLTRB(14, 0, 14, 10),
            child: GestureDetector(
              onTap: onSearchTap,
              child: Container(
                height: 42,
                padding: const EdgeInsets.symmetric(horizontal: 14),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: AppColors.lineStrong),
                ),
                child: const Row(
                  children: [
                    BrandIcon(
                      icon: BrandIconName.search,
                      size: 18,
                      strokeWidth: 1.6,
                      color: AppColors.muted,
                    ),
                    SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        'Search for products',
                        style: TextStyle(
                          color: AppColors.muted,
                          fontSize: 14,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

/// Pre-wired shared header for pushed browsing/content screens: back button
/// leading, logo resets to the home shell, icon taps navigate without ever
/// stacking the current route on itself. The shell itself keeps its own
/// tab-switching callbacks and does not use this helper.
EsteleAppBar pushedAppBar(BuildContext context) {
  final navigator = Navigator.of(context);
  final route = ModalRoute.of(context)?.settings.name;
  void noop() {}

  return EsteleAppBar(
    onMenuTap: null,
    onLogoTap: () => navigator.pushNamedAndRemoveUntil('/home', (_) => false),
    onSearchTap: route == '/search' ? noop : () => navigator.pushNamed('/search'),
    onWishlistTap:
        route == '/wishlist' ? noop : () => navigator.pushNamed('/wishlist'),
    onAccountTap: () {
      final authenticated =
          context.read<AuthProvider>().status == AuthStatus.authenticated;
      if (authenticated) {
        // The account tab lives in the shell — return to it.
        navigator.popUntil((r) => r.isFirst);
      } else {
        navigator.pushNamed('/login');
      }
    },
  );
}

/// Website-style header icon button — 20px glyph inside a 38px square.
class _HeaderIcon extends StatelessWidget {
  const _HeaderIcon({required this.onTap, required this.child});

  final VoidCallback onTap;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 38,
      height: 38,
      child: InkWell(
        onTap: onTap,
        customBorder: const CircleBorder(),
        child: Center(child: child),
      ),
    );
  }
}

/// Small rounded-rect count badge (`rounded-lg bg-accent`) that hides at 0.
class _BadgedIcon extends StatelessWidget {
  const _BadgedIcon({required this.count, required this.icon});

  final int count;
  final Widget icon;

  @override
  Widget build(BuildContext context) {
    return Stack(
      clipBehavior: Clip.none,
      children: [
        icon,
        if (count > 0)
          Positioned(
            right: -6,
            top: -5,
            child: Container(
              height: 16,
              constraints: const BoxConstraints(minWidth: 16),
              padding: const EdgeInsets.symmetric(horizontal: 4),
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: AppColors.accent,
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                count > 99 ? '99+' : '$count',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 10,
                  fontWeight: FontWeight.w600,
                  height: 1,
                ),
              ),
            ),
          ),
      ],
    );
  }
}
