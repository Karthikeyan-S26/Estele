import 'product.dart';
import 'product_variant.dart';

/// A line item inside the cart — mirrors `CartController::cartItemPayload`.
class CartItem {
  CartItem({
    required this.id,
    required this.product,
    this.variant,
    required this.unitPrice,
    required this.quantity,
    this.lineTotal = 0,
    this.availableStock = 0,
  });

  final int id;
  final Product product;
  final ProductVariant? variant;
  final double unitPrice;
  final int quantity;
  final double lineTotal;
  final int availableStock;

  factory CartItem.fromJson(Map<String, dynamic> json) {
    final productMap = json['product'] as Map<String, dynamic>? ?? const {};
    final variantMap = json['variant'] as Map<String, dynamic>?;

    return CartItem(
      id: json['id'] as int,
      product: Product.fromJson(productMap),
      variant: variantMap != null ? ProductVariant.fromJson(variantMap) : null,
      unitPrice: (json['unit_price'] as num?)?.toDouble() ?? 0,
      quantity: (json['quantity'] as num?)?.toInt() ?? 1,
      lineTotal: (json['line_total'] as num?)?.toDouble() ?? 0,
      availableStock: (json['available_stock'] as num?)?.toInt() ?? 0,
    );
  }
}

/// The cart totals block from the API.
class CartTotals {
  CartTotals({
    required this.subtotal,
    required this.discount,
    required this.shipping,
    required this.total,
    required this.shippingIsFree,
  });

  final double subtotal;
  final double discount;
  final double shipping;
  final double total;
  final bool shippingIsFree;

  factory CartTotals.fromJson(Map<String, dynamic> json) {
    return CartTotals(
      subtotal: (json['subtotal'] as num?)?.toDouble() ?? 0,
      discount: (json['discount'] as num?)?.toDouble() ?? 0,
      shipping: (json['shipping'] as num?)?.toDouble() ?? 0,
      total: (json['total'] as num?)?.toDouble() ?? 0,
      shippingIsFree: (json['shipping_is_free'] as bool?) ?? false,
    );
  }

  CartTotals copyWith({
    double? subtotal,
    double? discount,
    double? shipping,
    double? total,
    bool? shippingIsFree,
  }) {
    return CartTotals(
      subtotal: subtotal ?? this.subtotal,
      discount: discount ?? this.discount,
      shipping: shipping ?? this.shipping,
      total: total ?? this.total,
      shippingIsFree: shippingIsFree ?? this.shippingIsFree,
    );
  }
}

/// The full cart — mirrors `CartController::index`.
class Cart {
  Cart({
    required this.items,
    this.couponCode,
    this.couponSummary,
    required this.totals,
    required this.cartCount,
  });

  final List<CartItem> items;
  final String? couponCode;
  final String? couponSummary;
  final CartTotals totals;
  final int cartCount;

  factory Cart.fromJson(Map<String, dynamic> json) {
    final coupon = json['coupon'] as Map<String, dynamic>?;

    return Cart(
      items:
          (json['items'] as List<dynamic>?)
              ?.map((e) => CartItem.fromJson(e as Map<String, dynamic>))
              .toList() ??
          const [],
      couponCode: coupon?['code'] as String?,
      couponSummary: coupon?['summary'] as String?,
      totals: CartTotals.fromJson(
        (json['totals'] as Map<String, dynamic>?) ?? const {},
      ),
      cartCount: (json['cart_count'] as num?)?.toInt() ?? 0,
    );
  }

  bool get isEmpty => items.isEmpty;
}
