import '../../models/category.dart';
import '../../models/collection.dart';
import '../../models/home_data.dart';
import '../../models/product.dart';
import '../api_client.dart';

class CatalogRepository {
  static final _homeCache = _TtlCache<HomeData>();
  static final _categoryCache = _TtlCache<List<Category>>();
  static final _collectionCache = _TtlCache<List<Collection>>();

  /// Drop all in-memory list caches so the next call hits the API (used by
  /// pull-to-refresh on the home screen).
  static void clearCaches() {
    _homeCache.clear();
    _categoryCache.clear();
    _collectionCache.clear();
  }

  /// Everything the home screen needs in one call: categories, banners,
  /// content blocks (new-arrivals / sale / journal rows), and offers.
  static Future<HomeData> home() async {
    final cached = _homeCache.get();
    if (cached != null) return cached;
    final json = await ApiClient.get('/home');
    final data = HomeData.fromJson(json['data'] as Map<String, dynamic>);
    _homeCache.set(data);
    return data;
  }

  /// All categories for the Categories screen.
  static Future<List<Category>> categories() async {
    final cached = _categoryCache.get();
    if (cached != null) return cached;
    final json = await ApiClient.get('/categories');
    final data = (json['data'] as List<dynamic>? ?? [])
        .map((e) => Category.fromJson(e as Map<String, dynamic>))
        .toList();
    _categoryCache.set(data);
    return data;
  }

  /// Paginated products in a category, with optional sort.
  static Future<({List<Product> items, Map<String, dynamic> meta})>
  categoryProducts(
    String slug, {
    String sort = 'relevance',
    int page = 1,
    int perPage = 24,
  }) async {
    final json = await ApiClient.get(
      '/categories/$slug/products?page=$page&sort=$sort&per_page=$perPage',
    );
    return (
      items: (json['data'] as List<dynamic>? ?? [])
          .map((e) => Product.fromJson(e as Map<String, dynamic>))
          .toList(),
      meta: (json['meta'] as Map<String, dynamic>?) ?? const {},
    );
  }

  /// All active collections.
  static Future<List<Collection>> collections() async {
    final cached = _collectionCache.get();
    if (cached != null) return cached;
    final json = await ApiClient.get('/collections');
    final data = (json['data'] as List<dynamic>? ?? [])
        .map((e) => Collection.fromJson(e as Map<String, dynamic>))
        .toList();
    _collectionCache.set(data);
    return data;
  }

  /// Paginated products in a collection.
  static Future<({List<Product> items, Map<String, dynamic> meta})>
  collectionProducts(
    String slug, {
    String sort = 'relevance',
    int page = 1,
    int perPage = 24,
  }) async {
    final json = await ApiClient.get(
      '/collections/$slug/products?page=$page&sort=$sort&per_page=$perPage',
    );
    return (
      items: (json['data'] as List<dynamic>? ?? [])
          .map((e) => Product.fromJson(e as Map<String, dynamic>))
          .toList(),
      meta: (json['meta'] as Map<String, dynamic>?) ?? const {},
    );
  }

  /// Full product detail incl. related products and reviews.
  static Future<({ProductDetailPage page, List<Product> related})> product(
    String slug, {
    int page = 1,
  }) async {
    final json = await ApiClient.get('/products/$slug?page=$page');
    final data = json['data'] as Map<String, dynamic>;
    return (
      page: ProductDetailPage.fromJson(data),
      related: (data['related_products'] as List<dynamic>? ?? [])
          .map((e) => Product.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }

  /// POST /api/products/{slug}/reviews — submit a review (rating 1–5).
  /// The backend dedupes per user+product and marks verified purchases.
  static Future<String> storeReview({
    required String slug,
    required int rating,
    String? title,
    required String body,
  }) async {
    final json = await ApiClient.post(
      '/products/$slug/reviews',
      body: {
        'rating': rating,
        if (title != null && title.trim().isNotEmpty) 'title': title.trim(),
        'body': body.trim(),
      },
      auth: true,
    );
    return (json['message'] as String?) ?? 'Thanks for your review!';
  }

  /// Filtered search results.
  static Future<({List<Product> items, Map<String, dynamic> meta})> search(
    String query, {
    String sort = 'relevance',
    String? minPrice,
    String? maxPrice,
    bool inStock = false,
    List<String> categorySlugs = const [],
    int page = 1,
    int perPage = 24,
  }) async {
    final params = <String, String>{
      'q': query,
      'sort': sort,
      'page': page.toString(),
      'per_page': perPage.toString(),
      if (minPrice != null && minPrice.isNotEmpty) 'min_price': minPrice,
      if (maxPrice != null && maxPrice.isNotEmpty) 'max_price': maxPrice,
      if (inStock) 'in_stock': '1',
      for (final slug in categorySlugs) 'category[]': slug,
    };

    final queryString = params.entries
        .map((e) => '${e.key}=${Uri.encodeQueryComponent(e.value)}')
        .join('&');
    final json = await ApiClient.get('/search?$queryString');

    return (
      items: (json['data'] as List<dynamic>? ?? [])
          .map((e) => Product.fromJson(e as Map<String, dynamic>))
          .toList(),
      meta: (json['meta'] as Map<String, dynamic>?) ?? const {},
    );
  }

  /// Autocomplete suggestions.
  static Future<List<Product>> suggest(String query) async {
    if (query.trim().isEmpty) return [];
    final json = await ApiClient.get(
      '/search/suggest?q=${Uri.encodeQueryComponent(query)}',
    );
    return (json['data'] as List<dynamic>? ?? [])
        .map((e) => Product.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  /// Hydrate saved product ids into full cards (wishlist).
  static Future<List<Product>> wishlist(Iterable<int> ids) async {
    if (ids.isEmpty) return [];
    final idsQuery = ids.map((id) => 'ids[]=$id').join('&');
    final json = await ApiClient.get('/wishlist?$idsQuery');
    return (json['data'] as List<dynamic>? ?? [])
        .map((e) => Product.fromJson(e as Map<String, dynamic>))
        .toList();
  }
}

/// Parsed result of the product-detail endpoint.
class ProductDetailPage {
  ProductDetailPage({
    required this.product,
    required this.ratio,
    required this.count,
    required this.reviews,
  });

  final Product product;
  final double? ratio;
  final int count;
  final List<Map<String, dynamic>> reviews;

  factory ProductDetailPage.fromJson(Map<String, dynamic> data) {
    final summary = data['rating_summary'] as Map<String, dynamic>? ?? const {};
    final reviews = data['reviews'] as Map<String, dynamic>? ?? const {};

    return ProductDetailPage(
      product: Product.fromJson(data['product'] as Map<String, dynamic>),
      ratio: (summary['average'] as num?)?.toDouble(),
      count: (summary['count'] as num?)?.toInt() ?? 0,
      reviews: (reviews['data'] as List<dynamic>? ?? [])
          .cast<Map<String, dynamic>>(),
    );
  }
}

/// In-memory cache with a short time-to-live (2 min) so repeated visits to the
/// home / categories / collections endpoints within a session skip a network
/// round-trip while staying fresh enough to reflect admin edits.
class _TtlCache<T> {
  static const _ttl = Duration(minutes: 2);

  T? _value;
  DateTime? _expiresAt;

  T? get() {
    final value = _value;
    final expiresAt = _expiresAt;
    if (value == null || expiresAt == null) return null;
    if (DateTime.now().isAfter(expiresAt)) {
      _value = null;
      _expiresAt = null;
      return null;
    }
    return value;
  }

  void set(T value) {
    _value = value;
    _expiresAt = DateTime.now().add(_ttl);
  }

  void clear() {
    _value = null;
    _expiresAt = null;
  }
}
