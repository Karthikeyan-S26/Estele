import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:estele/models/product.dart';
import 'package:estele/providers/auth_provider.dart';
import 'package:estele/providers/cart_provider.dart';
import 'package:estele/providers/wishlist_provider.dart';
import 'package:estele/screens/product/product_detail_screen.dart';
import 'package:estele/theme/app_colors.dart';

Product _product({required int id, bool inStock = true}) {
  return Product(
    id: id,
    title: 'Peacock Necklace Set',
    slug: 'peacock-necklace-set',
    sku: 'SKU-$id',
    price: 1299,
    compareAtPrice: null,
    inStock: inStock,
    isNew: true,
    imageUrl: null,
  );
}

Future<void> _pumpPdp(WidgetTester tester, Product product) async {
  SharedPreferences.setMockInitialValues({});
  await tester.pumpWidget(
    MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthProvider()),
        ChangeNotifierProvider(create: (_) => CartProvider()),
        ChangeNotifierProvider(create: (_) => WishlistProvider()),
      ],
      child: MaterialApp(
        routes: {
          '/checkout': (_) => const Scaffold(body: Text('Checkout')),
        },
        home: ProductDetailScreen.preview(product: product),
      ),
    ),
  );
  await tester.pump();
}

void main() {
  testWidgets(
    'PDP sticky buy bar holds wishlist + BUY IT NOW + ADD TO BAG side by side',
    (tester) async {
      await _pumpPdp(tester, _product(id: 1));

      // Exactly one wishlist toggle on the whole screen — it lives in the
      // sticky bar, never in the gallery header.
      expect(find.byIcon(Icons.favorite_outline_rounded), findsOneWidget);

      // Bar buttons sit on the same row (website .buybar: outline Buy Now
      // then filled Add to Bag, both flex-1).
      final buyTop =
          tester.getTopLeft(find.widgetWithText(OutlinedButton, 'BUY IT NOW').last).dy;
      final addTop =
          tester.getTopLeft(find.widgetWithText(FilledButton, 'ADD TO BAG')).dy;
      expect((buyTop - addTop).abs(), lessThan(2.0));

      final buyNow = tester.widget<OutlinedButton>(
        find.widgetWithText(OutlinedButton, 'BUY IT NOW').last,
      );
      expect(buyNow.onPressed, isNotNull);
    },
  );

  testWidgets('BUY IT NOW label is NOT invisible white on the light canvas', (
    tester,
  ) async {
    await _pumpPdp(tester, _product(id: 4));

    // Regression: the label previously defaulted to AppTypography.button's
    // white, which OutlinedButton's DefaultTextStyle cannot override — white
    // text on a transparent/light button = invisible on-device. Every
    // BUY IT NOW label (inline + sticky bar) must carry NO explicit color
    // so the button paints it in its accent foreground.
    final labels = tester.widgetList<Text>(find.text('BUY IT NOW'));
    expect(labels, isNotEmpty);
    for (final label in labels) {
      expect(label.style?.color, isNull);
    }

    // The sticky bar renders last, after the inline content buttons.
    final button = tester.widget<OutlinedButton>(
      find.widgetWithText(OutlinedButton, 'BUY IT NOW').last,
    );
    expect(
      button.style?.foregroundColor?.resolve(<WidgetState>{}),
      AppColors.accent,
    );
    expect(
      button.style?.foregroundColor?.resolve({WidgetState.disabled}),
      AppColors.soldOut,
    );
  });

  testWidgets('BUY IT NOW is disabled when the piece is out of stock', (
    tester,
  ) async {
    await _pumpPdp(tester, _product(id: 2, inStock: false));

    expect(find.text('Out of stock'), findsOneWidget);
    expect(find.text('BUY IT NOW'), findsOneWidget);

    final buyNow = tester.widget<OutlinedButton>(
      find.widgetWithText(OutlinedButton, 'BUY IT NOW'),
    );
    expect(buyNow.onPressed, isNull);
  });
}