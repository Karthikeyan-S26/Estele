import 'package:flutter_test/flutter_test.dart';

import 'package:estele/models/cart.dart';
import 'package:estele/models/product.dart';
import 'package:estele/utils/formatters.dart';

Product _product({
  required int id,
  required double price,
  double? compareAt,
  bool inStock = true,
  bool isNew = false,
}) {
  return Product(
    id: id,
    title: 'Product $id',
    slug: 'product-$id',
    sku: 'SKU-$id',
    price: price,
    compareAtPrice: compareAt,
    inStock: inStock,
    isNew: isNew,
    imageUrl: null,
  );
}

void main() {
  group('formatINR', () {
    test('formats Indian grouping', () {
      expect(formatINR(100), '₹100');
      expect(formatINR(123456), '₹1,23,456');
      expect(formatINR(99999), '₹99,999');
      expect(formatINR(1000000), '₹10,00,000');
    });
  });

  group('Product', () {
    test('sale detection and discount percent', () {
      final onSale = _product(id: 1, price: 750, compareAt: 1000);
      expect(onSale.isOnSale, isTrue);
      expect(onSale.effectiveDiscountPercent, 25);

      final normal = _product(id: 2, price: 750);
      expect(normal.isOnSale, isFalse);
      expect(normal.effectiveDiscountPercent, 0);
    });

    test('copyWith preserves identity and toggles fields', () {
      final product = _product(id: 5, price: 100);
      final copy = product.copyWith(inStock: false);
      expect(copy.id, product.id);
      expect(copy.inStock, isFalse);
      expect(product.inStock, isTrue);
    });
  });

  group('Cart model parsing', () {
    test('parses an empty cart', () {
      final cart = Cart.fromJson(const {
        'items': [],
        'coupon': null,
        'totals': {'subtotal': 0, 'discount': 0, 'shipping': 0, 'total': 0, 'shipping_is_free': false},
        'cart_count': 0,
      });
      expect(cart.isEmpty, isTrue);
      expect(cart.cartCount, 0);
    });

    test('parses a populated cart', () {
      final cart = Cart.fromJson({
        'items': [
          {
            'id': 11,
            'product': {
              'id': 101,
              'title': 'Kundan Necklace',
              'slug': 'kundan-necklace',
              'sku': 'KN-1',
              'price': 1299,
              'image': 'https://cdn.example/card.jpg',
              'in_stock': true,
            },
            'unit_price': 1299,
            'quantity': 2,
            'line_total': 2598,
          },
        ],
        'coupon': {'code': 'ELIVE10', 'summary': '10% off'},
        'totals': {'subtotal': 2598, 'discount': 259.8, 'shipping': 0, 'total': 2338.2, 'shipping_is_free': true},
        'cart_count': 2,
      });

      expect(cart.isEmpty, isFalse);
      expect(cart.cartCount, 2);
      expect(cart.items.single.product.title, 'Kundan Necklace');
      expect(cart.items.single.unitPrice, 1299);
      expect(cart.couponCode, 'ELIVE10');
      expect(cart.couponSummary, '10% off');
      expect(cart.totals.discount, 259.8);
    });

    test('treats missing fields defensively', () {
      final cart = Cart.fromJson(const {});
      expect(cart.isEmpty, isTrue);
      expect(cart.cartCount, 0);
      expect(cart.couponCode, isNull);
    });
  });
}