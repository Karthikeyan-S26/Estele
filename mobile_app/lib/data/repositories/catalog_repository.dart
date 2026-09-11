import '../../models/category.dart';
import '../../models/collection.dart';
import '../../models/home_data.dart';
import '../../models/product.dart';
import '../api_client.dart';

class CatalogRepository {
  /// Everything the home screen needs in one call: categories, banners,
  /// content blocks (new-arrivals / sale / journal rows), and offers.
  static Future<HomeData> home() async {
    final json = await ApiClient.get('/home');
    return HomeData.fromJson(json['data'] as Map<String, dynamic>);
  }

  /// All categories for the Categories screen.
  static Future<List<Category>> categories() async {
    final json = await ApiClient.get('/categories');
    return (json['data'] as List<dynamic>? ?? [])
        .map((e) => Category.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  /// Paginated products in a category, with optional sort.
  static Future<({List<Product> items, Map<String, dynamic> meta})> categoryProducts(
    String slug, {
    String sort = 'relevance',
    int page = 1,
    int perPage = 24,
  }) async {
    final json = await ApiClient.get('/categories/$slug/products?page=$page&sort=$sort&per_page=$perPage');
    return (
      items: (json['data'] as List<dynamic>? ?? []).map((e) => Product.fromJson(e as Map<String, dynamic>)).toList(),
      meta: (json['meta'] as Map<String, dynamic>?) ?? const {},
    );
  }

  /// All active collections.
  static Future<List<Collection>> collections() async {
    final json = await ApiClient.get('/collections');
    return (json['data'] as List<dynamic>? ?? [])
        .map((e) => Collection.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  /// Paginated products in a collection.
  static Future<({List<Product> items, Map<String, dynamic> meta})> collectionProducts(
    String slug, {
    String sort = 'relevance',
    int page = 1,
    int perPage = 24,
  }) async {
    final json = await ApiClient.get('/collections/$slug/products?page=$page&sort=$sort&per_page=$perPage');
    return (
      items: (json['data'] as List<dynamic>? ?? []).map((e) => Product.fromJson(e as Map<String, dynamic>)).toList(),
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

    final queryString = params.entries.map((e) => '${e.key}=${Uri.encodeQueryComponent(e.value)}').join('&');
    final json = await ApiClient.get('/search?$queryString');

    return (
      items: (json['data'] as List<dynamic>? ?? []).map((e) => Product.fromJson(e as Map<String, dynamic>)).toList(),
      meta: (json['meta'] as Map<String, dynamic>?) ?? const {},
    );
  }

  /// Autocomplete suggestions.
  static Future<List<Product>> suggest(String query) async {
    if (query.trim().isEmpty) return [];
    final json = await ApiClient.get('/search/suggest?q=${Uri.encodeQueryComponent(query)}');
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
  ProductDetailPage({required this.product, required this.ratio, required this.count, required this.reviews});

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
      reviews: (reviews['data'] as List<dynamic>? ?? []).cast<Map<String, dynamic>>(),
    );
  }
}