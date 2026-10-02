import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';

import 'data/storage.dart';
import 'providers/auth_provider.dart';
import 'providers/cart_provider.dart';
import 'providers/wishlist_provider.dart';
import 'screens/addresses/address_book_screen.dart';
import 'screens/auth/login_screen.dart';
import 'screens/auth/otp_screen.dart';
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
import 'screens/splash_screen.dart';
import 'screens/sell/sell_create_screen.dart';
import 'screens/sell/sell_screen.dart';
import 'screens/stores/stores_screen.dart';
import 'screens/trending/trending_screen.dart';
import 'screens/wallet/wallet_screen.dart';
import 'screens/wishlist/wishlist_screen.dart';
import 'theme/app_theme.dart';

/// Root navigator key so a launcher relaunch (handled natively in
/// `MainActivity.onNewIntent`) can reset the whole stack to the splash.
final GlobalKey<NavigatorState> appNavigatorKey = GlobalKey<NavigatorState>();

/// Platform channel used by `MainActivity` to report a re-launch from the
/// device launcher icon. Estele is a single-Activity Flutter app, so a product
/// page lives in the Navigator, not its own Activity — this handler forces a
/// fresh splash -> Home instead of resuming the last viewed screen.
const MethodChannel _launcherChannel = MethodChannel('estele/launcher');

/// Registers the launcher-relaunch handler. Called from [main]; exposed so it
/// can be exercised in tests.
void registerLauncherResetHandler() {
  _launcherChannel.setMethodCallHandler((call) async {
    if (call.method == 'resetToHome') {
      appNavigatorKey.currentState?.pushNamedAndRemoveUntil(
        '/',
        (route) => false,
      );
    }
  });
}

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  // Fonts are bundled in assets/fonts — never fetch from the network at runtime.
  GoogleFonts.config.allowRuntimeFetching = false;
  await Storage.init();
  registerLauncherResetHandler();
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
        navigatorKey: appNavigatorKey,
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
        screen = const SplashScreen();
      case '/home':
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
      case '/otp':
        screen = OtpScreen(phone: settings.arguments as String);
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
