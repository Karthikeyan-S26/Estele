import 'product_variant.dart';

/// A product card / detail row — mirrors `App\Http\Api\ProductResource`.
///
/// The card image (`.image`) is what grids render; detail adds the full
/// gallery, variant list, categories and collections.
class Product {
  Product({
    required this.id,
    required this.title,
    required this.slug,
    required this.sku,
    required this.price,
    this.compareAtPrice,
    this.inStock = true,
    this.isActive = true,
    this.isFeatured = false,
    this.isNew = false,
    this.discountPercent,
    this.rating,
    this.reviewCount = 0,
    this.imageUrl,
    this.description,
    this.hasVariants = false,
    this.stockQuantity = 0,
    this.gallery = const [],
    this.variants = const [],
    this.categories = const [],
    this.collections = const [],
    this.createdAt,
  });

  final int id;
  final String title;
  final String slug;
  final String sku;
  final double price;
  final double? compareAtPrice;
  final bool inStock;
  final bool isActive;
  final bool isFeatured;
  final bool isNew;
  final int? discountPercent;
  final double? rating;
  final int reviewCount;
  final String? imageUrl;
  final String? description;
  final bool hasVariants;
  final int stockQuantity;
  final List<ProductImage> gallery;
  final List<ProductVariant> variants;
  final List<CategoryRef> categories;
  final List<CollectionRef> collections;
  final DateTime? createdAt;

  bool get isOnSale => compareAtPrice != null && compareAtPrice! > price;

  /// Discount badge value shown on cards — server sends it, but compute a
  /// fallback so the app never shows an empty badge on a sale item.
  int get effectiveDiscountPercent {
    if (discountPercent != null) return discountPercent!;
    if (isOnSale) {
      return ((compareAtPrice! - price) / compareAtPrice! * 100).round();
    }
    return 0;
  }

  factory Product.fromJson(Map<String, dynamic> json) {
    final imageMap = json['images'] as Map<String, dynamic>?;

    return Product(
      id: json['id'] as int,
      title: json['title'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
      sku: json['sku'] as String? ?? '',
      price: (json['price'] as num?)?.toDouble() ?? 0,
      compareAtPrice: (json['compare_at_price'] as num?)?.toDouble(),
      inStock: json['in_stock'] as bool? ?? true,
      isActive: json['is_active'] as bool? ?? true,
      isFeatured: json['is_featured'] as bool? ?? false,
      isNew: json['is_new'] as bool? ?? false,
      discountPercent: json['discount_percent'] as int?,
      rating: (json['rating'] as num?)?.toDouble(),
      reviewCount: (json['review_count'] as num?)?.toInt() ?? 0,
      imageUrl: json['image'] as String? ?? imageMap?['card'] as String?,
      description: json['description'] as String?,
      hasVariants: json['has_variants'] as bool? ?? false,
      stockQuantity: (json['stock_quantity'] as num?)?.toInt() ?? 0,
      gallery: (json['gallery'] as List<dynamic>?)
              ?.map((e) => ProductImage.fromJson(e as Map<String, dynamic>))
              .toList() ??
          const [],
      variants: (json['variants'] as List<dynamic>?)
              ?.map((e) => ProductVariant.fromJson(e as Map<String, dynamic>))
              .toList() ??
          const [],
      categories: (json['categories'] as List<dynamic>?)
              ?.map((e) => CategoryRef.fromJson(e as Map<String, dynamic>))
              .toList() ??
          const [],
      collections: (json['collections'] as List<dynamic>?)
              ?.map((e) => CollectionRef.fromJson(e as Map<String, dynamic>))
              .toList() ??
          const [],
      createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at'] as String) : null,
    );
  }

  /// Cheap copy for optimistic UI updates (wishlist toggle, etc.).
  Product copyWith({bool? inStock, bool? isNew, int? reviewCount, double? rating}) {
    return Product(
      id: id,
      title: title,
      slug: slug,
      sku: sku,
      price: price,
      compareAtPrice: compareAtPrice,
      inStock: inStock ?? this.inStock,
      isActive: isActive,
      isFeatured: isFeatured,
      isNew: isNew ?? this.isNew,
      discountPercent: discountPercent,
      rating: rating ?? this.rating,
      reviewCount: reviewCount ?? this.reviewCount,
      imageUrl: imageUrl,
      description: description,
      hasVariants: hasVariants,
      stockQuantity: stockQuantity,
      gallery: gallery,
      variants: variants,
      categories: categories,
      collections: collections,
      createdAt: createdAt,
    );
  }
}

/// A gallery image contained in `ProductDetail.gallery`.
class ProductImage {
  ProductImage({required this.id, required this.url, this.alt});

  final int id;
  final String url;
  final String? alt;

  factory ProductImage.fromJson(Map<String, dynamic> json) {
    return ProductImage(
      id: json['id'] as int,
      url: json['detail'] as String? ?? json['original'] as String? ?? '',
      alt: json['alt'] as String?,
    );
  }
}

class CategoryRef {
  CategoryRef({required this.id, required this.name, required this.slug});

  final int id;
  final String name;
  final String slug;

  factory CategoryRef.fromJson(Map<String, dynamic> json) {
    return CategoryRef(
      id: json['id'] as int,
      name: json['name'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
    );
  }
}

class CollectionRef {
  CollectionRef({required this.id, required this.name, required this.slug});

  final int id;
  final String name;
  final String slug;

  factory CollectionRef.fromJson(Map<String, dynamic> json) {
    return CollectionRef(
      id: json['id'] as int,
      name: json['name'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
    );
  }
}