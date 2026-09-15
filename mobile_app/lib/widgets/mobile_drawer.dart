import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../data/repositories/catalog_repository.dart';
import '../models/category.dart';
import '../models/collection.dart';
import '../providers/auth_provider.dart';
import '../theme/app_colors.dart';
import '../theme/app_typography.dart';
import '../screens/catalog/category_products_screen.dart';

/// The website's mobile drawer (`layouts/app.blade.php` `[data-drawer]`):
/// a left slide-in panel with a "Menu" header, collection links, expandable
/// "Shop by Category" / "Collections" groups, and a full-width accent
/// "Login / Register" (or "My Account") button.
class EsteleDrawer extends StatefulWidget {
  const EsteleDrawer({super.key, required this.onOpenAccount});

  /// Called (after the drawer closes) to jump the shell to the Account tab.
  final VoidCallback onOpenAccount;

  @override
  State<EsteleDrawer> createState() => _EsteleDrawerState();
}

class _EsteleDrawerState extends State<EsteleDrawer> {
  List<Category>? _categories;
  List<Collection>? _collections;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final results = await Future.wait([
        CatalogRepository.categories(),
        CatalogRepository.collections(),
      ]);
      if (mounted) {
        setState(() {
          _categories = results[0] as List<Category>;
          _collections = results[1] as List<Collection>;
        });
      }
    } catch (_) {
      // Non-fatal: drawer still renders with the accesible link rows.
    }
  }

  void _closeAndPush(Widget screen) {
    Navigator.of(context).pop();
    Navigator.of(context).push(MaterialPageRoute(builder: (_) => screen));
  }

  @override
  Widget build(BuildContext context) {
    final width = MediaQuery.sizeOf(context).width;
    final authenticated =
        context.watch<AuthProvider>().status == AuthStatus.authenticated;
    final categories = _categories ?? const <Category>[];
    final collections = _collections ?? const <Collection>[];

    return Drawer(
      width: (width * 0.85).clamp(0, 320),
      shape: const RoundedRectangleBorder(),
      backgroundColor: AppColors.ivory,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Header — "Menu" + close.
          Container(
            decoration: const BoxDecoration(
              border: Border(bottom: BorderSide(color: AppColors.line)),
            ),
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 18),
            child: Row(
              children: [
                Expanded(
                  child: Text(
                    'Menu',
                    style: AppTypography.bodyMedium(
                      size: 13,
                      color: AppColors.heading,
                      weight: FontWeight.w500,
                    ).copyWith(letterSpacing: 0.5),
                  ),
                ),
                InkWell(
                  onTap: () => Navigator.of(context).pop(),
                  child: const SizedBox(
                    width: 28,
                    height: 28,
                    child: Icon(
                      Icons.close_rounded,
                      color: AppColors.heading,
                      size: 24,
                    ),
                  ),
                ),
              ],
            ),
          ),
          // Navigation list.
          Expanded(
            child: ListView(
              padding: EdgeInsets.zero,
              children: [
                for (final collection in collections)
                  _lineItem(
                    label: collection.name.toUpperCase(),
                    onTap: () => _closeAndPush(
                      CategoryProductsScreen(
                        title: collection.name,
                        categorySlug: collection.slug,
                        isCollection: true,
                      ),
                    ),
                  ),
                if (categories.isNotEmpty)
                  _expandableGroup(
                    title: 'Shop by Category',
                    children: categories,
                    buildLabel: (c) => c.name,
                    onTap: (c) => _closeAndPush(
                      CategoryProductsScreen(
                        title: c.name,
                        categorySlug: c.slug,
                      ),
                    ),
                  ),
                if (collections.isNotEmpty)
                  _expandableGroup(
                    title: 'Collections',
                    children: collections,
                    buildLabel: (c) => c.name,
                    onTap: (c) => _closeAndPush(
                      CategoryProductsScreen(
                        title: c.name,
                        categorySlug: c.slug,
                        isCollection: true,
                      ),
                    ),
                  ),
              ],
            ),
          ),
          // CTA.
          Container(
            padding: const EdgeInsets.all(20),
            child: ElevatedButton(
              onPressed: () {
                Navigator.of(context).pop();
                if (authenticated) {
                  widget.onOpenAccount();
                } else {
                  Navigator.of(context).pushNamed('/login');
                }
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.accent,
                foregroundColor: Colors.white,
                minimumSize: const Size.fromHeight(46),
                padding: const EdgeInsets.symmetric(vertical: 13),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(0),
                ),
              ),
              child: Text(
                authenticated ? 'MY ACCOUNT' : 'LOGIN / REGISTER',
                style: AppTypography.button(size: 13, letterSpacing: 0.5),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _lineItem({required String label, required VoidCallback onTap}) {
    return InkWell(
      onTap: onTap,
      child: Container(
        decoration: const BoxDecoration(
          border: Border(bottom: BorderSide(color: AppColors.line)),
        ),
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
        child: Text(
          label,
          style: AppTypography.bodyMedium(
            size: 13,
            color: AppColors.heading,
          ).copyWith(letterSpacing: 0.3),
        ),
      ),
    );
  }

  Widget _expandableGroup<T>({
    required String title,
    required List<T> children,
    required String Function(T) buildLabel,
    required void Function(T) onTap,
  }) {
    return Container(
      decoration: const BoxDecoration(
        border: Border(bottom: BorderSide(color: AppColors.line)),
      ),
      child: ExpansionTile(
        tilePadding: const EdgeInsets.symmetric(horizontal: 20),
        childrenPadding: EdgeInsets.zero,
        iconColor: AppColors.heading,
        collapsedIconColor: AppColors.heading,
        title: Text(
          title.toUpperCase(),
          style: AppTypography.bodyMedium(
            size: 13,
            color: AppColors.heading,
          ).copyWith(letterSpacing: 0.3),
        ),
        children: [
          for (final child in children)
            InkWell(
              onTap: () => onTap(child),
              child: Container(
                width: double.infinity,
                color: Colors.white.withValues(alpha: 0.6),
                padding: EdgeInsets.fromLTRB(34, 12, 20, 12),
                child: Text(
                  buildLabel(child),
                  style: AppTypography.bodySmall(
                    size: 12.5,
                    color: AppColors.muted,
                  ).copyWith(height: 1.3),
                ),
              ),
            ),
        ],
      ),
    );
  }
}
