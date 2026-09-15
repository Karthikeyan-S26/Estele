import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../data/repositories/account_repository.dart';
import '../../data/repositories/auth_repository.dart';
import '../../models/user.dart';
import '../../providers/auth_provider.dart';
import '../../providers/wishlist_provider.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_typography.dart';
import '../../widgets/load_state.dart';
import '../auth/login_screen.dart';
import 'edit_profile_screen.dart';

class AccountScreen extends StatefulWidget {
  const AccountScreen({super.key});

  @override
  State<AccountScreen> createState() => _AccountScreenState();
}

class _AccountScreenState extends State<AccountScreen> {
  User? _profile;
  bool _loadingFailed = false;
  late final AuthProvider _authProvider;

  @override
  void initState() {
    super.initState();
    _authProvider = context.read<AuthProvider>();
    // Re-fetch the profile whenever auth changes (e.g. the user just signed
    // in from this tab), instead of showing the pre-login stale state.
    _authProvider.addListener(_onAuthChanged);
    _refresh();
  }

  @override
  void dispose() {
    _authProvider.removeListener(_onAuthChanged);
    super.dispose();
  }

  void _onAuthChanged() {
    if (!mounted) return;
    _refresh();
  }

  Future<void> _refresh() async {
    final loggedIn = await AuthRepository.isLoggedIn();
    if (!loggedIn) return;
    try {
      final profile = await AccountRepository.profile();
      if (mounted) {
        setState(() {
          _profile = profile;
          _loadingFailed = false;
        });
      }
    } catch (_) {
      if (mounted) setState(() => _loadingFailed = true);
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final wishlist = context.watch<WishlistProvider>();

    final isGuest = !auth.isAuthenticated;

    return RefreshIndicator(
      onRefresh: _refresh,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        children: [
          Text('My account', style: AppTypography.scriptAccent(size: 30)),
          Text(
            isGuest ? 'Welcome' : _profile?.name ?? _displayName(auth),
            style: AppTypography.sectionTitle(size: 20),
          ),
          const SizedBox(height: 4),
          Text(
            isGuest
                ? 'Sign in to see your orders, addresses and more.'
                : (_profile?.email ?? auth.user?.email ?? ''),
            style: AppTypography.bodySmall(color: AppColors.muted),
          ),
          const SizedBox(height: 18),

          if (isGuest) ...[
            FilledButton(
              onPressed: () => Navigator.of(
                context,
              ).push(MaterialPageRoute(builder: (_) => const LoginScreen())),
              child: const Text('Sign in / Create account'),
            ),
            const SizedBox(height: 24),
            const _GuestNote(),
          ] else ...[
            if (_loadingFailed)
              Container(
                margin: const EdgeInsets.only(bottom: 10),
                child: LoadState.error(
                  message: 'Could not refresh your profile.',
                  onRetry: _refresh,
                ),
              ),
            // Menu group
            _GroupLabel('Shop'),
            _Tile(
              icon: Icons.receipt_long_outlined,
              title: 'My orders',
              onTap: () => Navigator.of(context).pushNamed('/orders'),
            ),
            _Tile(
              icon: Icons.favorite_outline_rounded,
              title: 'Wishlist',
              trailing: wishlist.count > 0
                  ? Text('${wishlist.count}', style: AppTypography.bodySmall())
                  : null,
              onTap: () => Navigator.of(context).pushNamed('/wishlist'),
            ),
            _Tile(
              icon: Icons.location_on_outlined,
              title: 'Address book',
              onTap: () => Navigator.of(context).pushNamed('/addresses'),
            ),
            _Tile(
              icon: Icons.sell_outlined,
              title: 'Sell gold',
              subtitle: 'Old jewellery buy-back',
              onTap: () => Navigator.of(context).pushNamed('/sell'),
            ),
            _Tile(
              icon: Icons.store_outlined,
              title: 'Our stores',
              subtitle: 'Bandra flagship · upcoming outlets',
              onTap: () => Navigator.of(context).pushNamed('/stores'),
            ),

            _GroupLabel('Account'),
            _Tile(
              icon: Icons.person_outline_rounded,
              title: 'Edit profile',
              onTap: () async {
                await Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => const EditProfileScreen()),
                );
                if (mounted) _refresh();
              },
            ),
            _Tile(
              icon: Icons.wallet_outlined,
              title: 'Wallet',
              trailing: _profile != null
                  ? Text(
                      '₹${_profile!.walletBalance.round()}',
                      style: AppTypography.bodyMedium(
                        weight: FontWeight.w600,
                        color: AppColors.accent,
                      ),
                    )
                  : null,
              onTap: () => Navigator.of(context).pushNamed('/wallet'),
            ),
            _Tile(
              icon: Icons.help_outline_rounded,
              title: 'Help & FAQ',
              onTap: () => Navigator.of(context).pushNamed('/faq'),
            ),

            _GroupLabel('Legal'),
            _Tile(
              icon: Icons.info_outline_rounded,
              title: 'About Estele',
              onTap: () => Navigator.of(context).pushNamed('/cms/about-us'),
            ),
            _Tile(
              icon: Icons.privacy_tip_outlined,
              title: 'Privacy policy',
              onTap: () =>
                  Navigator.of(context).pushNamed('/cms/privacy-policy'),
            ),
            _Tile(
              icon: Icons.local_shipping_outlined,
              title: 'Shipping & returns',
              onTap: () =>
                  Navigator.of(context).pushNamed('/cms/shipping-policy'),
            ),

            const SizedBox(height: 16),
            OutlinedButton.icon(
              onPressed: () async {
                final navigator = Navigator.of(context);
                await auth.logout();
                if (mounted) {
                  setState(() => _profile = null);
                  navigator.popUntil((r) => r.isFirst);
                }
              },
              icon: const Icon(Icons.logout_rounded, size: 18),
              label: const Text('Sign out'),
              style: OutlinedButton.styleFrom(foregroundColor: AppColors.error),
            ),
          ],
        ],
      ),
    );
  }

  String _displayName(AuthProvider auth) {
    final name = auth.user?.name;
    if (name != null && name.isNotEmpty) return name;
    return 'Welcome back';
  }
}

class _GuestNote extends StatelessWidget {
  const _GuestNote();

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'With an account you can:',
          style: AppTypography.bodyMedium(weight: FontWeight.w600),
        ),
        const SizedBox(height: 8),
        for (final line in const [
          'Track every order easily',
          'Save addresses for faster checkout',
          'Use your Estele wallet balance',
          'Write and read verified reviews',
        ])
          Padding(
            padding: const EdgeInsets.only(bottom: 6),
            child: Row(
              children: [
                const Icon(
                  Icons.check_rounded,
                  size: 16,
                  color: AppColors.gold,
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    line,
                    style: AppTypography.body(
                      size: 13.5,
                      color: AppColors.muted,
                    ),
                  ),
                ),
              ],
            ),
          ),
      ],
    );
  }
}

class _GroupLabel extends StatelessWidget {
  const _GroupLabel(this.text);

  final String text;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(0, 18, 0, 8),
      child: Text(
        text,
        style: AppTypography.label(color: AppColors.muted, letterSpacing: 1.4),
      ),
    );
  }
}

class _Tile extends StatelessWidget {
  const _Tile({
    required this.icon,
    required this.title,
    this.subtitle,
    this.trailing,
    required this.onTap,
  });

  final IconData icon;
  final String title;
  final String? subtitle;
  final Widget? trailing;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      decoration: BoxDecoration(
        color: AppColors.paper,
        borderRadius: BorderRadius.circular(4),
        border: Border.all(color: AppColors.line),
      ),
      child: ListTile(
        dense: true,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(4)),
        leading: Icon(icon, size: 21, color: AppColors.accentDark),
        title: Text(
          title,
          style: AppTypography.bodyMedium(weight: FontWeight.w500),
        ),
        subtitle: subtitle != null
            ? Text(subtitle!, style: AppTypography.bodySmall(size: 11.5))
            : null,
        trailing:
            trailing ??
            const Icon(Icons.chevron_right_rounded, color: AppColors.muted),
        onTap: onTap,
      ),
    );
  }
}
