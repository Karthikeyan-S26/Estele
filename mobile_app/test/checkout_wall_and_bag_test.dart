import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:estele/models/cart.dart';
import 'package:estele/models/product.dart';
import 'package:estele/providers/auth_provider.dart';
import 'package:estele/providers/cart_provider.dart';
import 'package:estele/providers/wishlist_provider.dart';
import 'package:estele/screens/cart/cart_screen.dart';
import 'package:estele/screens/cart/checkout_screen.dart';

Future<void> _pumpWith(
  WidgetTester tester,
  Widget home, {
  CartProvider? cart,
}) async {
  SharedPreferences.setMockInitialValues({});
  const channel = MethodChannel('plugins.it_nomadas.com/flutter_secure_storage');
  tester.binding.defaultBinaryMessenger.setMockMethodCallHandler(
    channel,
    (call) async => null,
  );
  await tester.pumpWidget(
    MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthProvider()),
        ChangeNotifierProvider(create: (_) => cart ?? CartProvider()),
        ChangeNotifierProvider(create: (_) => WishlistProvider()),
      ],
      child: MaterialApp(home: home),
    ),
  );
  await tester.pump();
  await tester.pump();
}

CartProvider _bagWithItems() {
  final provider = CartProvider();
  provider.cart = Cart(
    items: [
      CartItem(
        id: 1,
        product: Product(
          id: 1,
          title: 'Peacock Necklace Set',
          slug: 'peacock-necklace-set',
          sku: 'SKU-1',
          price: 1000,
          compareAtPrice: null,
          inStock: true,
          imageUrl: null,
        ),
        unitPrice: 1000,
        quantity: 2,
        lineTotal: 2000,
      ),
    ],
    couponCode: null,
    totals: CartTotals(
      subtotal: 2000,
      discount: 0,
      shipping: 0,
      total: 2000,
      shippingIsFree: true,
    ),
    cartCount: 2,
  );
  return provider;
}

void main() {
  group('CheckoutScreen', () {
    testWidgets('guests see the sign-in wall (reactive auth gate)',
        (tester) async {
      await _pumpWith(tester, const CheckoutScreen());

      expect(find.text('Sign in to checkout'), findsOneWidget);
      expect(find.text('Sign in / Create account'), findsOneWidget);
    });
  });

  group('CartScreen', () {
    testWidgets('bag shows website order summary line items',
        (tester) async {
      await _pumpWith(tester, const CartScreen(), cart: _bagWithItems());

      expect(find.text('Item Total'), findsOneWidget);
      expect(find.text('Shipping'), findsOneWidget);
      expect(find.text('GST'), findsOneWidget);
      expect(find.text('Included'), findsOneWidget);
      // Appears in the summary box and in the bottom payment bar.
      expect(find.text('Total Payable'), findsWidgets);
    });

    testWidgets('bag shows coupon entry + honest View all coupons affordance',
        (tester) async {
      await _pumpWith(tester, const CartScreen(), cart: _bagWithItems());

      expect(find.text('Coupon code'), findsOneWidget);
      expect(find.text('Apply'), findsOneWidget);
      expect(find.text('View all coupons'), findsOneWidget);

      await tester.tap(find.text('View all coupons'));
      await tester.pumpAndSettle();

      expect(find.text('Coupon offers'), findsOneWidget);
    });

testWidgets('bag shows the security & trust strip', (tester) async {
        await _pumpWith(tester, const CartScreen(), cart: _bagWithItems());

        // The strip sits below the fold in the ListView — scroll to it.
        await tester.dragUntilVisible(
          find.text(
            'Secure payments · 15-day easy returns · 18+ years of trust',
          ),
          find.byType(ListView),
          const Offset(0, -200),
        );
        await tester.pump();

        expect(
          find.text(
            'Secure payments · 15-day easy returns · 18+ years of trust',
          ),
          findsOneWidget,
        );
      });
  });
}