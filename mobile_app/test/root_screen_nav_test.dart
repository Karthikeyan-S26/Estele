import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';

import 'package:estele/providers/auth_provider.dart';
import 'package:estele/providers/cart_provider.dart';
import 'package:estele/providers/wishlist_provider.dart';
import 'package:estele/screens/catalog/categories_screen.dart';
import 'package:estele/screens/catalog/home_screen.dart';
import 'package:estele/screens/root_screen.dart';
import 'package:estele/screens/account/account_screen.dart';
import 'package:estele/screens/wishlist/wishlist_screen.dart';

/// Pump [RootScreen] inside the same [MultiProvider] shell the real app uses.
Future<void> _pumpRoot(WidgetTester tester) async {
  await tester.pumpWidget(
    MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthProvider()),
        ChangeNotifierProvider(create: (_) => CartProvider()),
        ChangeNotifierProvider(create: (_) => WishlistProvider()),
      ],
      child: const MaterialApp(home: RootScreen()),
    ),
  );
  // Let async init (network that returns 400 in test) settle across tabs.
  await tester.pump();
  await tester.pump();
}

void main() {
  testWidgets('bottom nav switches to Home (default)', (tester) async {
    await _pumpRoot(tester);

    final stack = tester.widget<IndexedStack>(find.byType(IndexedStack));
    expect(stack.index, 0);
    expect(find.byType(HomeScreen), findsOneWidget);
  });

  testWidgets('tap Categories switches to tab 1', (tester) async {
    await _pumpRoot(tester);

    await tester.tap(find.text('CATEGORIES'));
    await tester.pump();

    final stack = tester.widget<IndexedStack>(find.byType(IndexedStack));
    expect(stack.index, 1);
    expect(find.byType(CategoriesScreen), findsOneWidget);
  });

  testWidgets('tap Wishlist switches to tab 2', (tester) async {
    await _pumpRoot(tester);

    await tester.tap(find.text('WISHLIST'));
    await tester.pump();

    final stack = tester.widget<IndexedStack>(find.byType(IndexedStack));
    expect(stack.index, 2);
    expect(find.byType(WishlistView), findsOneWidget);
  });

  testWidgets('tap Account switches to tab 3', (tester) async {
    await _pumpRoot(tester);

    await tester.tap(find.text('ACCOUNT'));
    await tester.pump();

    final stack = tester.widget<IndexedStack>(find.byType(IndexedStack));
    expect(stack.index, 3);
    expect(find.byType(AccountScreen), findsOneWidget);
  });

  testWidgets(
    'full switching cycle: Home → Categories → Wishlist → Account → Home',
    (tester) async {
      await _pumpRoot(tester);

      Future<void> assertTab(int index, String label) async {
        await tester.tap(find.text(label));
        await tester.pump();
        final stack = tester.widget<IndexedStack>(find.byType(IndexedStack));
        expect(
          stack.index,
          index,
          reason: 'Expected tab $index after tapping $label',
        );
      }

      await assertTab(1, 'CATEGORIES');
      await assertTab(2, 'WISHLIST');
      await assertTab(3, 'ACCOUNT');
      await assertTab(0, 'HOME');
    },
  );

  // Dispose the tree so any pending timers (HeroCarousel, PromoBar if data
  // somehow loaded) are cancelled and don't leak across tests.
  tearDown(() async {});
}
