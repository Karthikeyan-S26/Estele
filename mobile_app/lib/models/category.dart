class Category {
  Category({
    required this.id,
    required this.name,
    required this.slug,
    this.description,
    this.imageUrl,
    this.productCount = 0,
    this.parentId,
    this.children = const [],
  });

  final int id;
  final String name;
  final String slug;
  final String? description;
  final String? imageUrl;
  final int productCount;
  final int? parentId;
  final List<Category> children;

  factory Category.fromJson(Map<String, dynamic> json) {
    return Category(
      id: json['id'] as int,
      name: json['name'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
      description: json['description'] as String?,
      imageUrl: json['image'] as String?,
      productCount: (json['product_count'] as num?)?.toInt() ?? 0,
      parentId: json['parent_id'] as int?,
      children: (json['children'] as List<dynamic>?)
              ?.map((e) => Category.fromJson(e as Map<String, dynamic>))
              .toList() ??
          const [],
    );
  }
}