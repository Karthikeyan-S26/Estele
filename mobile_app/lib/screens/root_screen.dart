import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../providers/cart_provider.dart';
import '../theme/app_colors.dart';
import '../theme/app_typography.dart';
import 'account/account_screen.dart';
import 'catalog/categories_screen.dart';
import 'catalog/home_screen.dart';
import 'stores/stores_screen.dart';
import 'trending/trending_screen.dart';

/// The app shell: a fixed gold-line app bar with the ESTELE wordmark (gold
/// leaf gradient — the one place it's allowed), a search entry field, cart
/// badge, and the 5-tab bottom navigation.
class RootScreen extends StatefulWidget {
  const RootScreen({super.key});

  @override
  State<RootScreen> createState() => _RootScreenState();
}

class _RootScreenState extends State<RootScreen> {
  int _index = 0;

  static const _tabs = [
    HomeScreen(),
    CategoriesScreen(),
    TrendingScreen(),
    StoresScreen(),
    AccountScreen(),
  ];

  @override
  Widget build(BuildContext context) {
    final cartCount = context.select<CartProvider, int>((c) => c.cart.cartCount);

    return Scaffold(
      appBar: AppBar(
        toolbarHeight: 64,
        title: Row(
          children: [
            Expanded(
              child: GestureDetector(
                onTap: () => setState(() => _index = 0),
                child: Align(
                  alignment: Alignment.centerLeft,
                  child: AppTypography.logo(fontSize: 22),
                ),
              ),
            ),
            // Search entry
            IconButton(
              icon: const Icon(Icons.search_rounded, color: AppColors.heading),
              onPressed: () => Navigator.of(context).pushNamed('/search'),
              tooltip: 'Search',
            ),
            // Wishlist shortcut
            IconButton(
              icon: const Icon(Icons.favorite_outline_rounded, color: AppColors.heading),
              onPressed: () => Navigator.of(context).pushNamed('/wishlist'),
              tooltip: 'Wishlist',
            ),
            // Cart badge
            Stack(
              clipBehavior: Clip.none,
              children: [
                IconButton(
                  icon: const Icon(Icons.shopping_bag_outlined, color: AppColors.heading),
                  onPressed: () => Navigator.of(context).pushNamed('/cart'),
                  tooltip: 'Cart',
                ),
                if (cartCount > 0)
                  Positioned(
                    right: 2,
                    top: 2,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                      decoration: const BoxDecoration(
                        color: AppColors.accent,
                        shape: BoxShape.circle,
                      ),
                      child: Text(
                        cartCount.toString(),
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 10,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ),
              ],
            ),
          ],
        ),
      ),
      body: IndexedStack(index: _index, children: _tabs),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _index,
        onTap: (i) => setState(() => _index = i),
        items: const [
          BottomNavigationBarItem(icon: Icon(Icons.home_outlined), activeIcon: Icon(Icons.home_rounded), label: 'Home'),
          BottomNavigationBarItem(
            icon: Icon(Icons.apps_outlined),
            activeIcon: Icon(Icons.apps_rounded),
            label: 'Categories',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.local_fire_department_outlined),
            activeIcon: Icon(Icons.local_fire_department_rounded),
            label: 'Trending',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.storefront_outlined),
            activeIcon: Icon(Icons.storefront_rounded),
            label: 'Stores',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.person_outline_rounded),
            activeIcon: Icon(Icons.person_rounded),
            label: 'Account',
          ),
        ],
      ),
    );
  }
}