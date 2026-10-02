import '../../models/blog_post.dart';
import '../../models/cms_page.dart';
import '../../models/faq_item.dart';
import '../api_client.dart';

class ContentRepository {
  static Future<List<BlogPost>> blog({int page = 1, int perPage = 12}) async {
    final json = await ApiClient.get('/blogs?page=$page&per_page=$perPage');
    return (json['data'] as List<dynamic>? ?? [])
        .map((e) => BlogPost.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  static Future<BlogPost> blogPost(String slug) async {
    final json = await ApiClient.get('/blogs/$slug');
    final data = json['data'] as Map<String, dynamic>? ?? const {};
    return BlogPost.fromJson(data['post'] as Map<String, dynamic>);
  }

  static Future<List<FaqCategory>> faqs() async {
    final json = await ApiClient.get('/faq');
    return (json['data'] as List<dynamic>? ?? [])
        .map((e) => FaqCategory.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  static Future<CmsPage> page(String slug) async {
    final json = await ApiClient.get('/pages/$slug');
    return CmsPage.fromJson(json['data'] as Map<String, dynamic>);
  }

  /// Store info for the Stores / Store locator screens.
  static Future<List<Map<String, dynamic>>> stores() async {
    final json = await ApiClient.get('/stores');
    return (json['data'] as List<dynamic>? ?? []).cast<Map<String, dynamic>>();
  }

  /// Newsletter email capture ("Get the Glow" popup) — POSTs to the same
  /// endpoint as the website popup. Idempotent server-side (firstOrCreate),
  /// so an already-subscribed address also returns success. Throws
  /// [ApiException] with the server message on validation/network failure.
  static Future<String> subscribeNewsletter(String email) async {
    final json = await ApiClient.post(
      '/newsletter/subscribe',
      body: {'email': email.trim()},
    );
    return (json['message'] as String?) ?? 'Subscribed. Thank you!';
  }
}
