import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../data/api_client.dart';
import '../data/repositories/cart_repository.dart';
import '../models/cart.dart';

class CartProvider extends ChangeNotifier {
  Cart cart = Cart(
    items: const [],
    couponCode: null,
    totals: CartTotals(
      subtotal: 0,
      discount: 0,
      shipping: 0,
      total: 0,
      shippingIsFree: false,
    ),
    cartCount: 0,
  );

  bool initialLoading = false;
  bool busy = false;
  String? lastError;

  Future<void> load() async {
    if (initialLoading) return;
    initialLoading = true;
    try {
      cart = await CartRepository.fetch();
      _persistBadge();
    } on ApiException catch (e) {
      lastError = e.message;
    } catch (_) {
      lastError = 'Unable to reach the server.';
    } finally {
      initialLoading = false;
      notifyListeners();
    }
  }

  Future<String?> addItem({
    required int productId,
    required String productSlug,
    int? variantId,
    int quantity = 1,
  }) async {
    lastError = null;
    _optimisticAdd(productId, variantId, quantity);
    try {
      cart = await CartRepository.addItem(
        productSlug: productSlug,
        variantId: variantId,
        quantity: quantity,
      );
    } on ApiException catch (e) {
      lastError = e.message;
      return e.message;
    } catch (_) {
      lastError = 'Unable to reach the server.';
      return lastError;
    } finally {
      _persistBadge();
      notifyListeners();
    }
    return null;
  }

  /// Optimistically bump the badge + totals so the UI never stutters.
  /// Full item state arrives with the server response moments later.
  void _optimisticAdd(int productId, int? variantId, int quantity) {
    final updated = [
      for (final item in cart.items)
        if (item.product.id == productId && item.variant?.id == variantId)
          CartItem(
            id: item.id,
            product: item.product,
            variant: item.variant,
            unitPrice: item.unitPrice,
            quantity: item.quantity + quantity,
            lineTotal: item.unitPrice * (item.quantity + quantity),
            availableStock: item.availableStock,
          )
        else
          item,
    ];
    cart = Cart(
      items: updated,
      couponCode: cart.couponCode,
      couponSummary: cart.couponSummary,
      totals: _recompute(updated, cart.couponSummary),
      cartCount: _totalQty(updated),
    );
  }

  Future<String?> updateItem({
    required int cartItemId,
    required int quantity,
  }) async {
    if (quantity < 1) {
      return removeItem(cartItemId);
    }
    lastError = null;
    _optimisticQty(cartItemId, quantity);
    try {
      cart = await CartRepository.updateItem(
        cartItemId: cartItemId,
        quantity: quantity,
      );
    } on ApiException catch (e) {
      lastError = e.message;
      return e.message;
    } catch (_) {
      lastError = 'Unable to reach the server.';
      return lastError;
    } finally {
      _persistBadge();
      notifyListeners();
    }
    return null;
  }

  void _optimisticQty(int cartItemId, int quantity) {
    final updated = [
      for (final item in cart.items)
        if (item.id == cartItemId)
          CartItem(
            id: item.id,
            product: item.product,
            variant: item.variant,
            unitPrice: item.unitPrice,
            quantity: quantity,
            lineTotal: item.unitPrice * quantity,
            availableStock: item.availableStock,
          )
        else
          item,
    ];
    cart = Cart(
      items: updated,
      couponCode: cart.couponCode,
      couponSummary: cart.couponSummary,
      totals: _recompute(updated, cart.couponSummary),
      cartCount: _totalQty(updated),
    );
  }

  Future<String?> removeItem(int cartItemId) async {
    lastError = null;
    final preview = [
      for (final item in cart.items)
        if (item.id != cartItemId) item,
    ];
    cart = Cart(
      items: preview,
      couponCode: cart.couponCode,
      couponSummary: cart.couponSummary,
      totals: _recompute(preview, cart.couponSummary),
      cartCount: _totalQty(preview),
    );
    _persistBadge();
    notifyListeners();
    try {
      cart = await CartRepository.removeItem(cartItemId);
    } on ApiException catch (e) {
      lastError = e.message;
      return e.message;
    } catch (_) {
      lastError = 'Unable to reach the server.';
      return lastError;
    } finally {
      _persistBadge();
      notifyListeners();
    }
    return null;
  }

  Future<String?> applyCoupon(String code) async {
    if (code.trim().isEmpty) return 'Enter a coupon code.';
    lastError = null;
    try {
      cart = await CartRepository.applyCoupon(code.trim());
    } on ApiException catch (e) {
      return e.message;
    } catch (_) {
      return 'Unable to reach the server.';
    } finally {
      _persistBadge();
      notifyListeners();
    }
    return null;
  }

  Future<String?> removeCoupon() async {
    lastError = null;
    try {
      cart = await CartRepository.removeCoupon();
    } on ApiException catch (e) {
      return e.message;
    } catch (_) {
      return 'Unable to reach the server.';
    } finally {
      notifyListeners();
    }
    return null;
  }

  Future<void> clear() async {
    cart = Cart(
      items: const [],
      couponCode: null,
      totals: CartTotals(
        subtotal: 0,
        discount: 0,
        shipping: 0,
        total: 0,
        shippingIsFree: false,
      ),
      cartCount: 0,
    );
    _persistBadge();
    notifyListeners();
    try {
      await CartRepository.clear();
    } catch (_) {}
  }

  /// After login/register the guest cart gets merged server-side; simply
  /// re-fetch so the merged cart (incl. coupon) is reflected.
  Future<void> mergeAfterAuth() => load();

  /// Optimistic totals while awaiting the server. Discount/shipping are kept
  /// from the last server response (never zeroed) so changing a quantity
  /// doesn't flash a stripped-down total — the server recomputes the real
  /// values milliseconds later.
  CartTotals _recompute(List<CartItem> items, String? _) {
    final previous = cart.totals;
    final subtotal = items.fold<double>(0, (sum, i) => sum + i.lineTotal);
    final discount = previous.discount;
    final shipping = previous.shipping;
    final total = (subtotal - discount) + shipping;
    return CartTotals(
      subtotal: subtotal,
      discount: discount,
      shipping: shipping,
      total: total >= 0 ? total : 0,
      shippingIsFree: previous.shippingIsFree,
    );
  }

  int _totalQty(List<CartItem> items) =>
      items.fold<int>(0, (sum, i) => sum + i.quantity);

  Future<void> _persistBadge() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setInt('cart_badge_count', cart.cartCount);
  }
}
