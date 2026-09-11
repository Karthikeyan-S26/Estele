/// A journal/blog post — mirrors the API blog payload.
class BlogPost {
  BlogPost({
    required this.id,
    required this.title,
    required this.slug,
    this.excerpt,
    this.content,
    this.author,
    this.category,
    this.categorySlug,
    this.publishedAt,
    this.isFeatured = false,
    this.imageUrl,
    this.detailImageUrl,
  });

  final int id;
  final String title;
  final String slug;
  final String? excerpt;
  final String? content;
  final String? author;
  final String? category;
  final String? categorySlug;
  final DateTime? publishedAt;
  final bool isFeatured;
  final String? imageUrl;
  final String? detailImageUrl;

  factory BlogPost.fromJson(Map<String, dynamic> json) {
    return BlogPost(
      id: json['id'] as int,
      title: json['title'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
      excerpt: json['excerpt'] as String?,
      content: json['content'] as String?,
      author: json['author'] as String?,
      category: json['category'] as String?,
      categorySlug: json['category_slug'] as String?,
      publishedAt: json['published_at'] != null ? DateTime.tryParse(json['published_at'] as String) : null,
      isFeatured: json['is_featured'] as bool? ?? false,
      imageUrl: json['image'] as String?,
      detailImageUrl: json['detail_image'] as String?,
    );
  }
}