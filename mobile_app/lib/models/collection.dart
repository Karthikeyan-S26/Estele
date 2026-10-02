class Collection {
  Collection({
    required this.id,
    required this.name,
    required this.slug,
    this.description,
    this.imageUrl,
    this.productCount = 0,
  });

  final int id;
  final String name;
  final String slug;
  final String? description;
  final String? imageUrl;
  final int productCount;

  factory Collection.fromJson(Map<String, dynamic> json) {
    return Collection(
      id: json['id'] as int,
      name: json['name'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
      description: json['description'] as String?,
      imageUrl: json['image'] as String?,
      productCount: (json['product_count'] as num?)?.toInt() ?? 0,
    );
  }
}
