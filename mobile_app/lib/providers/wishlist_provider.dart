import 'package:flutter/foundation.dart';

import '../data/repositories/catalog_repository.dart';
import '../data/storage.dart';
import '../models/product.dart';

/// Wishlist is local-first (instant toggle, persisted in prefs) and hydrates
/// into full product cards from the server (`/wishlist?ids[]=`) on demand.
class WishlistProvider extends ChangeNotifier {
  final Set<int> _ids = {};
  List<Product> _products = [];
  bool hydrated = false;

  List<int> get ids => _ids.toList();
  List<Product> get products => _products;
  bool isWishlisted(int productId) => _ids.contains(productId);
  int get count => _ids.length;

  WishlistProvider() {
    _ids.addAll(Storage.getWishlistIds());
  }

  Future<void> hydrate() async {
    if (_ids.isEmpty) {
      hydrated = true;
      notifyListeners();
      return;
    }
    try {
      _products = await CatalogRepository.wishlist(_ids);
    } catch (_) {
      // Offline — keep cards empty; toggles still work locally.
    }
    hydrated = true;
    notifyListeners();
  }

  Future<void> toggle(int productId, {Product? product}) async {
    if (_ids.contains(productId)) {
      _ids.remove(productId);
      _products.removeWhere((p) => p.id == productId);
    } else {
      _ids.add(productId);
      if (product != null) _products.insert(0, product);
    }
    await Storage.saveWishlistIds(_ids.toList());
    notifyListeners();
  }

  void addProduct(Product product) {
    if (_ids.add(product.id)) {
      _products.insert(0, product);
      Storage.saveWishlistIds(_ids.toList());
      notifyListeners();
    }
  }

  void removeProduct(int productId) {
    if (_ids.remove(productId)) {
      _products.removeWhere((p) => p.id == productId);
      Storage.saveWishlistIds(_ids.toList());
      notifyListeners();
    }
  }

  Future<void> clear() async {
    _ids.clear();
    _products.clear();
    await Storage.saveWishlistIds(const []);
    notifyListeners();
  }
}