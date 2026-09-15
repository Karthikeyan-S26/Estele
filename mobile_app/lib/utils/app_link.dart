import 'package:flutter/material.dart';

import '../screens/catalog/categories_screen.dart';
import '../screens/catalog/category_products_screen.dart';
import '../screens/stores/stores_screen.dart';
import '../screens/trending/trending_screen.dart';

/// Resolves a backend href (e.g. `/collections/rings`, `/product/slug`,
/// `/blog/all/deep-dive`, `/faq`) into a push on the root navigator.
///
/// Handles both relative app hrefs and absolute URLs (which can leak from
/// seed CTA values) — the path portion always drives navigation, so a stray
/// `http://localhost:8000` CTA simply becomes a no-op instead of a broken
/// `/cms/http:` push.
void resolveAppLink(BuildContext context, String? href) {
  if (href == null || href.isEmpty) return;

  final uri = Uri.tryParse(href);
  if (uri == null) return;

  final segments = uri.pathSegments
      .map((s) => s.trim())
      .where((s) => s.isNotEmpty)
      .toList();
  if (segments.isEmpty) return;

  final navigator = Navigator.of(context);
  final type = segments.first.toLowerCase();
  final slug = segments.length > 1 ? segments[1] : null;

  switch (type) {
    case 'product':
    case 'products':
      if (slug != null) navigator.pushNamed('/product/$slug');
      break;
    case 'category':
    case 'categories':
      if (slug != null) {
        navigator.push(
          MaterialPageRoute(
            builder: (_) => CategoryProductsScreen(
              title: _titleFromSlug(slug),
              categorySlug: slug,
              isCollection: false,
            ),
          ),
        );
      } else {
        navigator.push(
          MaterialPageRoute(builder: (_) => const CategoriesScreen()),
        );
      }
      break;
    case 'collection':
    case 'collections':
      if (slug != null) {
        navigator.push(
          MaterialPageRoute(
            builder: (_) => CategoryProductsScreen(
              title: _titleFromSlug(slug),
              categorySlug: slug,
              isCollection: true,
            ),
          ),
        );
      } else {
        navigator.push(
          MaterialPageRoute(builder: (_) => const CategoriesScreen()),
        );
      }
      break;
    case 'blog':
    case 'blogs':
      if (slug != null && slug != type) {
        navigator.pushNamed('/blog/$slug');
      } else {
        navigator.pushNamed('/blog');
      }
      break;
    case 'faq':
    case 'faqs':
      if (slug != null && slug != type) {
        navigator.pushNamed('/cms/$slug');
      } else {
        navigator.pushNamed('/faq');
      }
      break;
    case 'search':
      navigator.pushNamed('/search');
      break;
    case 'trending':
      navigator.push(MaterialPageRoute(builder: (_) => const TrendingScreen()));
      break;
    case 'stores':
      navigator.push(MaterialPageRoute(builder: (_) => const StoresScreen()));
      break;
    default:
      navigator.pushNamed('/cms/$type');
  }
}

String _titleFromSlug(String slug) {
  return slug.split('-').map(_capitalize).join(' ');
}

String _capitalize(String w) =>
    w.isEmpty ? w : w[0].toUpperCase() + w.substring(1);
