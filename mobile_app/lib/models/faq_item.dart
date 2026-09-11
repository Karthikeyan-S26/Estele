class FaqItem {
  FaqItem({required this.id, required this.question, required this.answer});

  final int id;
  final String question;
  final String answer;

  factory FaqItem.fromJson(Map<String, dynamic> json) {
    return FaqItem(
      id: json['id'] as int,
      question: json['question'] as String? ?? '',
      answer: json['answer'] as String? ?? '',
    );
  }
}

class FaqCategory {
  FaqCategory({required this.id, required this.name, this.faqs = const []});

  final int id;
  final String name;
  final List<FaqItem> faqs;

  factory FaqCategory.fromJson(Map<String, dynamic> json) {
    return FaqCategory(
      id: json['id'] as int,
      name: json['name'] as String? ?? '',
      faqs: (json['faqs'] as List<dynamic>?)
              ?.map((e) => FaqItem.fromJson(e as Map<String, dynamic>))
              .toList() ??
          const [],
    );
  }
}