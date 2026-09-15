/// A CMS page (About, Privacy, Shipping, etc.).
class CmsPage {
  CmsPage({
    required this.id,
    required this.title,
    required this.slug,
    required this.content,
  });

  final int id;
  final String title;
  final String slug;
  final String content;

  factory CmsPage.fromJson(Map<String, dynamic> json) {
    return CmsPage(
      id: json['id'] as int,
      title: json['title'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
      content: json['content'] as String? ?? '',
    );
  }
}
