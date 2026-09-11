/// A product size/option variant — mirrors `ProductResource::detail().variants`.
class ProductVariant {
  ProductVariant({
    required this.id,
    required this.sku,
    required this.price,
    required this.stockQuantity,
    required this.inStock,
    this.attributes = const {},
  });

  final int id;
  final String sku;
  final double price;
  final int stockQuantity;
  final bool inStock;
  final Map<String, dynamic> attributes;

  /// Human-friendly variant label from the attributes map, e.g. "Size: S".
  String get label {
    if (attributes.isEmpty) return sku;
    return attributes.values.join(' / ');
  }

  factory ProductVariant.fromJson(Map<String, dynamic> json) {
    return ProductVariant(
      id: json['id'] as int,
      sku: json['sku'] as String? ?? '',
      price: (json['price'] as num?)?.toDouble() ?? 0,
      stockQuantity: (json['stock_quantity'] as num?)?.toInt() ?? 0,
      inStock: json['in_stock'] as bool? ?? ((json['stock_quantity'] as num?)?.toInt() ?? 0) > 0,
      attributes: (json['attributes'] as Map<String, dynamic>?) ?? const {},
    );
  }
}