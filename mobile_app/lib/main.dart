import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';

import 'data/storage.dart';
import 'providers/auth_provider.dart';
import 'providers/cart_provider.dart';
import 'providers/wishlist_provider.dart';
import 'screens/addresses/address_book_screen.dart';
import 'screens/auth/login_screen.dart';
import 'screens/auth/register_screen.dart';
import 'screens/blog/blog_post_screen.dart';
import 'screens/blog/blog_screen.dart';
import 'screens/cart/cart_screen.dart';
import 'screens/cart/checkout_screen.dart';
import 'screens/content/cms_page_screen.dart';
import 'screens/content/faq_screen.dart';
import 'screens/orders/order_detail_screen.dart';
import 'screens/orders/orders_screen.dart';
import 'screens/product/product_detail_screen.dart';
import 'screens/root_screen.dart';
import 'screens/search/search_screen.dart';
import 'screens/sell/sell_create_screen.dart';
import 'screens/sell/sell_screen.dart';
import 'screens/stores/stores_screen.dart';
import 'screens/trending/trending_screen.dart';
import 'screens/wallet/wallet_screen.dart';
import 'screens/wishlist/wishlist_screen.dart';
import 'theme/app_theme.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  // Fonts are bundled in assets/fonts — never fetch from the network at runtime.
  GoogleFonts.config.allowRuntimeFetching = false;
  await Storage.init();
  runApp(const EsteleApp());
}

class EsteleApp extends StatelessWidget {
  const EsteleApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthProvider()),
        ChangeNotifierProvider(create: (_) => CartProvider()),
        ChangeNotifierProvider(create: (_) => WishlistProvider()),
      ],
      child: MaterialApp(
        title: 'Estele',
        debugShowCheckedModeBanner: false,
        theme: AppTheme.light,
        initialRoute: '/',
        onGenerateRoute: _generateRoute,
      ),
    );
  }

  /// Named routes — `/cms/:slug`, `/orders/:id` and `/product/:slug` use
  /// their dynamic segment directly.
  static Route<dynamic>? _generateRoute(RouteSettings settings) {
    final name = settings.name ?? '/';

    Widget? screen;
    switch (name) {
      case '/':
        screen = const RootScreen();
      case '/search':
        screen = const SearchScreen();
      case '/wishlist':
        screen = const WishlistScreen();
      case '/cart':
        screen = const CartScreen();
      case '/checkout':
        screen = const CheckoutScreen();
      case '/login':
        screen = const LoginScreen();
      case '/register':
        screen = RegisterScreen(prefillPhone: settings.arguments as String?);
      case '/orders':
        screen = const OrdersScreen();
      case '/addresses':
        screen = const AddressBookScreen();
      case '/wallet':
        screen = const WalletScreen();
      case '/sell':
        screen = const SellScreen();
      case '/sell/create':
        screen = const SellCreateScreen();
      case '/trending':
        screen = const TrendingScreen();
      case '/stores':
        screen = const StoresScreen();
      case '/faq':
        screen = const FaqScreen();
      case '/blog':
        screen = const BlogScreen();
    }

    if (screen == null) {
      // Dynamic segments
      if (name.startsWith('/product/')) {
        final slug = name.substring('/product/'.length);
        screen = ProductDetailScreen(slug: slug);
      } else if (name.startsWith('/orders/')) {
        final orderNumber = name.substring('/orders/'.length);
        screen = orderNumber.isNotEmpty
            ? OrderDetailScreen(orderNumber: orderNumber)
            : null;
      } else if (name.startsWith('/cms/')) {
        final slug = name.substring('/cms/'.length);
        screen = CmsPageScreen(slug: slug);
      } else if (name.startsWith('/blog/')) {
        final slug = name.substring('/blog/'.length);
        screen = BlogPostScreen(slug: slug);
      }
    }

    if (screen == null) return null;

    return MaterialPageRoute(settings: settings, builder: (_) => screen!);
  }
}
