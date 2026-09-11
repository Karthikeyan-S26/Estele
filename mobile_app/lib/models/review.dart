/// A customer review on a product.
class Review {
  Review({
    required this.id,
    required this.rating,
    this.title,
    this.body,
    this.customerName,
    this.isVerifiedPurchase = false,
    this.date,
    this.photos = const [],
  });

  final int id;
  final int rating;
  final String? title;
  final String? body;
  final String? customerName;
  final bool isVerifiedPurchase;
  final DateTime? date;
  final List<String> photos;

  factory Review.fromJson(Map<String, dynamic> json) {
    return Review(
      id: json['id'] as int,
      rating: (json['rating'] as num?)?.toInt() ?? 5,
      title: json['title'] as String?,
      body: json['body'] as String?,
      customerName: json['customer_name'] as String?,
      isVerifiedPurchase: json['is_verified_purchase'] as bool? ?? false,
      date: (json['review_date'] ?? json['created_at']) != null
          ? DateTime.tryParse((json['review_date'] ?? json['created_at']) as String)
          : null,
      photos: ((json['photos'] as List<dynamic>?) ?? const [])
          .map((e) => e as String)
          .toList(),
    );
  }
}