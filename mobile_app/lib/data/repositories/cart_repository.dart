import '../../models/cart.dart';
import '../api_client.dart';

class CartRepository {
  /// Hits the cart API as the currently signed-in user when a bearer token
  /// exists (that resolves the *user's* cart server-side — the backend reads
  /// the token first, falls back to the X-Cart-Token guest path).
  static Future<Cart> fetch() async {
    final json = await ApiClient.get('/cart', auth: true, requireCartToken: true);
    return Cart.fromJson(json['data'] as Map<String, dynamic>);
  }

  static Future<Cart> addItem({
    required String productSlug,
    int? variantId,
    int quantity = 1,
  }) async {
    final json = await ApiClient.post(
      '/cart/$productSlug',
      body: {'product_variant_id': ?variantId, 'quantity': quantity},
      auth: true,
      requireCartToken: true,
    );
    return Cart.fromJson(json['data'] as Map<String, dynamic>);
  }

  static Future<Cart> updateItem({
    required int cartItemId,
    required int quantity,
  }) async {
    final json = await ApiClient.patch(
      '/cart/items/$cartItemId',
      body: {'quantity': quantity},
      auth: true,
      requireCartToken: true,
    );
    return Cart.fromJson(json['data'] as Map<String, dynamic>);
  }

  static Future<Cart> removeItem(int cartItemId) async {
    final json = await ApiClient.delete(
      '/cart/items/$cartItemId',
      auth: true,
      requireCartToken: true,
    );
    return Cart.fromJson(json['data'] as Map<String, dynamic>);
  }

  static Future<Cart> applyCoupon(String code) async {
    final json = await ApiClient.post(
      '/cart/coupon',
      body: {'code': code},
      auth: true,
      requireCartToken: true,
    );
    return Cart.fromJson(json['data'] as Map<String, dynamic>);
  }

  static Future<Cart> removeCoupon() async {
    final json = await ApiClient.delete(
      '/cart/coupon',
      auth: true,
      requireCartToken: true,
    );
    return Cart.fromJson(json['data'] as Map<String, dynamic>);
  }

  static Future<void> clear() async {
    await ApiClient.delete('/cart', auth: true, requireCartToken: true);
  }

  /// Re-fetch totals so the checkout summary stays fresh after a price change.
  static Future<Cart> refreshTotals() async => fetch();
}
