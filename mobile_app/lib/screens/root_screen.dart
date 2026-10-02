import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../data/api_client.dart';
import '../providers/auth_provider.dart';
import '../providers/cart_provider.dart';
import '../providers/wishlist_provider.dart';
import '../theme/app_colors.dart';
import '../widgets/brand_icons.dart';
import '../widgets/chat_widget.dart';
import '../widgets/estele_app_bar.dart';
import '../widgets/mobile_drawer.dart';
import 'account/account_screen.dart';
import 'catalog/categories_screen.dart';
import 'catalog/home_screen.dart';
import 'wishlist/wishlist_screen.dart';

/// The app shell: an app bar that mirrors the website's mobile header
/// (hamburger · ESTELE wordmark · search / wishlist / cart / account icons and
/// the always-visible "Search for products" pill), the 4-tab bottom navigation
/// (Home · Categories · Wishlist · Account), the support-chat bubble and the
/// mobile drawer.
///
/// Tabs are created lazily — only the selected tab is instantiated, and once
/// touched a tab stays alive (IndexedStack) so screens keep their scroll
/// position and loaded state across switches.
class RootScreen extends StatefulWidget {
  const RootScreen({super.key});

  @override
  State<RootScreen> createState() => _RootScreenState();
}

class _RootScreenState extends State<RootScreen> {
  int _index = 0;
  AuthStatus _lastAuthStatus = AuthStatus.unknown;
  late final AuthProvider _authProvider;
  final _scaffoldKey = GlobalKey<ScaffoldState>();

  final List<Widget?> _tabBodies = List<Widget?>.filled(4, null);

  static const _tabs = [
    _TabSpec(label: 'Home', icon: BrandIconName.home),
    _TabSpec(label: 'Categories', icon: BrandIconName.grid),
    _TabSpec(label: 'Wishlist', icon: BrandIconName.heart),
    _TabSpec(label: 'Account', icon: BrandIconName.user),
  ];

  @override
  void initState() {
    super.initState();
    _authProvider = context.read<AuthProvider>();
    _lastAuthStatus = _authProvider.status;
    _authProvider.addListener(_onAuthChanged);

    // Expired / revoked token → make the shell drop the dead session (no
    // server round-trip, the token is already invalid) so screens show the
    // sign-in wall instead of a bare error banner.
    ApiClient.onUnauthorized = () {
      _authProvider.forceLogoutLocal();
    };
  }

  @override
  void dispose() {
    // Hold the provider reference (not `context.read` — illegal during the
    // deactivated phase when this runs).
    _authProvider.removeListener(_onAuthChanged);
    super.dispose();
  }

  /// Cart lifecycle across auth transitions:
  ///  - session restored / logged in  → re-fetch the (merged) user cart so the
  ///    badge and totals are correct from the start;
  ///  - logged out / session expired  → re-fetch the device guest cart.
  void _onAuthChanged() {
    final status = context.read<AuthProvider>().status;
    if (status == AuthStatus.unknown || status == _lastAuthStatus) return;
    _lastAuthStatus = status;

    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      final cart = context.read<CartProvider>();
      if (status == AuthStatus.authenticated) {
        cart.mergeAfterAuth();
      } else {
        cart.load();
      }
    });
  }

  Widget? _buildTab(int i) {
    switch (i) {
      case 0:
        return const HomeScreen();
      case 1:
        return const CategoriesScreen();
      case 2:
        return const WishlistView();
      case 3:
        return const AccountScreen();
    }
    return null;
  }

  /// Truly lazy tab bodies: an IndexedStack child is only mounted after its
  /// tab is first selected (this keeps a brand-new tab from calling
  /// `notifyListeners()` inside the build phase — which previously made the
  /// bottom nav appear dead on first run).
  Widget _lazyTabBody(int i) {
    if (_tabBodies[i] == null && i == _index) {
      _tabBodies[i] = _buildTab(i);
    }
    return _tabBodies[i] ?? const SizedBox.shrink();
  }

  void _openAccount() {
    final authenticated =
        context.read<AuthProvider>().status == AuthStatus.authenticated;
    if (authenticated) {
      setState(() => _index = 3);
    } else {
      Navigator.of(context).pushNamed('/login');
    }
  }

  void _openSearch() {
    Navigator.of(context).pushNamed('/search');
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      key: _scaffoldKey,
      drawer: EsteleDrawer(onOpenAccount: () => setState(() => _index = 3)),
      // The single shared header (see widgets/estele_app_bar.dart) — same
      // tree as before, now owned by one widget so pushed screens render the
      // identical bar. Scaffold pins it: content scrolls underneath.
      appBar: EsteleAppBar(
        onMenuTap: () => _scaffoldKey.currentState?.openDrawer(),
        onLogoTap: () => setState(() => _index = 0),
        onSearchTap: _openSearch,
        onWishlistTap: () => setState(() => _index = 2),
        onAccountTap: _openAccount,
      ),
      // IndexedStack keeps each built tab alive between switches; the support
      // chat bubble floats above the content, clear of the bottom bar.
      body: Stack(
        children: [
          IndexedStack(
            index: _index,
            children: [for (var i = 0; i < _tabs.length; i++) _lazyTabBody(i)],
          ),
          // Chat bubble clears the bottom bar exactly like the web's
          // `fixed bottom-[74px]` (nav ≈ 50px tall → ~24px clearance).
          Positioned(right: 12, bottom: 24, child: ChatWidget()),
        ],
      ),
      bottomNavigationBar: _EsteleBottomBar(
        tabs: _tabs,
        onTap: (i) {
          setState(() => _index = i);
          // Wishlist tab hydrates after sign-in/out so the recently-toggled
          // state is reflected immediately.
          if (i == 2 && mounted) {
            final wishlist = context.read<WishlistProvider>();
            if (wishlist.hydrated &&
                wishlist.products.length != wishlist.ids.length) {
              wishlist.hydrate();
            }
          }
        },
      ),
    );
  }
}

class _TabSpec {
  const _TabSpec({required this.label, required this.icon});

  final String label;
  final BrandIconName icon;
}

/// Header icon + badge widgets now live with the shared header
/// (see widgets/estele_app_bar.dart) — this file keeps only shell chrome.

/// The website's mobile bottom navigation replicant: four equal items, white
/// bar with a soft top border, 10px uppercase labels with wide tracking. The
/// web has no active "accent" state on mobile — every item is `text-heading`
/// (accent only appears on `hover`), so all items render in heading color.
class _EsteleBottomBar extends StatelessWidget {
  const _EsteleBottomBar({
    required this.tabs,
    required this.onTap,
  });

  final List<_TabSpec> tabs;
  final ValueChanged<int> onTap;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: AppColors.paper,
        border: Border(top: BorderSide(color: AppColors.line)),
      ),
      child: SafeArea(
        top: false,
        child: Row(
          children: [
            for (var i = 0; i < tabs.length; i++)
              Expanded(
                child: InkWell(
                  onTap: () => onTap(i),
                  // py-2 px-0.5 gap-0.5 (web)
                  child: Padding(
                    padding: const EdgeInsets.symmetric(
                      vertical: 8,
                      horizontal: 2,
                    ),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        BrandIcon(
                          icon: tabs[i].icon,
                          size: 20,
                          strokeWidth: 1.5,
                          color: AppColors.heading, // text-heading on all items
                        ),
                        const SizedBox(height: 2), // gap-0.5 = 2px
                        Text(
                          tabs[i].label.toUpperCase(),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.w400, // web default
                            letterSpacing: 0.3, // tracking-[0.3px] absolute
                            color: AppColors.heading,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
