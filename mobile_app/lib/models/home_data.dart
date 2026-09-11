import 'category.dart';
import 'collection.dart';
import 'faq_item.dart';
import 'product.dart';

/// Parsed `/home` endpoint response.
///
/// The backend now serializes the full homepage as named sections — each one
/// assembled from real CMS rows (HomepageBlock / HomepageBlockItem + Settings),
/// mirroring the web homepage top to bottom:
/// promo → hero → categories → collection banner → trending → collections →
/// budget tiers → new arrivals → bestsellers → celebrities → benefits →
/// testimonials → journal → instagram → stats → faqs → services → footer.
class HomeData {
  const HomeData({
    this.promo = const [],
    this.heroBanners = const [],
    this.categories = const [],
    this.collectionBanners = const [],
    this.trendingProducts = const [],
    this.trendingCta,
    this.collections = const [],
    this.newArrivals = const [],
    this.newArrivalsCta,
    this.bestsellers = const [],
    this.bestsellersCta,
    this.priceTiers = const [],
    this.celebrities = const [],
    this.benefits = const [],
    this.testimonials = const [],
    this.journal,
    this.instagram,
    this.stats,
    this.faqs = const [],
    this.services = const [],
    this.footer,
    this.offers = const [],
  });

  final List<String> promo;
  final List<HomeBanner> heroBanners;
  final List<Category> categories;
  final List<CollectionBanner> collectionBanners;
  final List<Product> trendingProducts;
  final String? trendingCta;
  final List<Collection> collections;
  final List<Product> newArrivals;
  final String? newArrivalsCta;
  final List<Product> bestsellers;
  final String? bestsellersCta;
  final List<PriceTier> priceTiers;
  final List<Celebrity> celebrities;
  final List<Benefit> benefits;
  final List<Testimonial> testimonials;
  final JournalSection? journal;
  final InstagramSection? instagram;
  final StatsSection? stats;
  final List<FaqItem> faqs;
  final List<ServiceBenefit> services;
  final FooterData? footer;
  final List<String> offers;

  factory HomeData.fromJson(Map<String, dynamic> json) {
    List<T> parseList<T>(String key, T Function(Map<String, dynamic>) fromJson) {
      return (json[key] as List<dynamic>? ?? [])
          .whereType<Map<String, dynamic>>()
          .map(fromJson)
          .toList();
    }

    return HomeData(
      promo: (json['promo'] as List<dynamic>? ?? []).map((e) => e.toString()).toList(),
      heroBanners: parseList('hero_banners', HomeBanner.fromJson),
      categories: parseList('categories', Category.fromJson),
      collectionBanners: parseList('collection_banners', CollectionBanner.fromJson),
      trendingProducts: parseList('trending_products', Product.fromJson),
      trendingCta: json['trending_cta'] as String?,
      collections: parseList('collections', Collection.fromJson),
      newArrivals: parseList('new_arrivals', Product.fromJson),
      newArrivalsCta: json['new_arrivals_cta'] as String?,
      bestsellers: parseList('bestsellers', Product.fromJson),
      bestsellersCta: json['bestsellers_cta'] as String?,
      priceTiers: parseList('price_tiers', PriceTier.fromJson),
      celebrities: parseList('celebrities', Celebrity.fromJson),
      benefits: parseList('benefits', Benefit.fromJson),
      testimonials: parseList('testimonials', Testimonial.fromJson),
      journal: json['journal'] == null ? null : JournalSection.fromJson(json['journal'] as Map<String, dynamic>),
      instagram: json['instagram'] == null
          ? null
          : InstagramSection.fromJson(json['instagram'] as Map<String, dynamic>),
      stats: json['stats'] == null
          ? null
          : StatsSection.fromJson(json['stats'] as Map<String, dynamic>),
      faqs: parseList('faqs', FaqItem.fromJson),
      services: parseList('services', ServiceBenefit.fromJson),
      footer: json['footer'] == null ? null : FooterData.fromJson(json['footer'] as Map<String, dynamic>),
      offers: (json['offers'] as List<dynamic>? ?? []).map((e) => e.toString()).toList(),
    );
  }
}

class HomeBanner {
  const HomeBanner({
    required this.id,
    this.imageUrl,
    this.mobileImageUrl,
    this.title,
    this.subtitle,
    this.linkUrl,
    this.cta,
  });

  final int id;
  final String? imageUrl;
  final String? mobileImageUrl;
  final String? title;
  final String? subtitle;
  final String? linkUrl;
  final String? cta;

  factory HomeBanner.fromJson(Map<String, dynamic> json) {
    return HomeBanner(
      id: json['id'] as int? ?? 0,
      imageUrl: json['image'] as String?,
      mobileImageUrl: json['mobile_image'] as String? ?? json['image'] as String?,
      title: json['title'] as String?,
      subtitle: json['subtitle'] as String?,
      linkUrl: json['link_url'] as String? ?? json['link'] as String?,
      cta: json['cta'] as String?,
    );
  }
}

/// The full-bleed "Rose Gold Collection" banner under the categories row.
class CollectionBanner {
  const CollectionBanner({
    this.id,
    required this.title,
    this.subtitle,
    this.image,
    this.linkUrl,
    this.collection,
  });

  final int? id;
  final String title;
  final String? subtitle;
  final String? image;
  final String? linkUrl;
  final Collection? collection;

  factory CollectionBanner.fromJson(Map<String, dynamic> json) {
    return CollectionBanner(
      id: json['id'] as int?,
      title: json['title'] as String? ?? '',
      subtitle: json['subtitle'] as String?,
      image: json['image'] as String?,
      linkUrl: json['link_url'] as String?,
      collection: json['collection'] == null
          ? null
          : Collection.fromJson(json['collection'] as Map<String, dynamic>),
    );
  }
}

/// A "Under ₹499 / Premium ₹2,000+" budget tile.
class PriceTier {
  const PriceTier({this.id, required this.label, required this.amount, this.image});

  final int? id;
  final String label;
  final String amount;
  final String? image;

  factory PriceTier.fromJson(Map<String, dynamic> json) {
    return PriceTier(
      id: json['id'] as int?,
      label: json['label'] as String? ?? '',
      amount: json['amount'] as String? ?? '',
      image: json['image'] as String?,
    );
  }
}

class Celebrity {
  const Celebrity({this.id, required this.title, this.image});

  final int? id;
  final String title;
  final String? image;

  factory Celebrity.fromJson(Map<String, dynamic> json) {
    return Celebrity(
      id: json['id'] as int?,
      title: json['title'] as String? ?? '',
      image: json['image'] as String?,
    );
  }
}

class Benefit {
  const Benefit({this.id, required this.title, this.body});

  final int? id;
  final String title;
  final String? body;

  factory Benefit.fromJson(Map<String, dynamic> json) {
    return Benefit(
      id: json['id'] as int?,
      title: json['title'] as String? ?? '',
      body: json['body'] as String?,
    );
  }
}

class Testimonial {
  const Testimonial({this.id, required this.title, this.body, this.rating});

  final int? id;
  final String title;
  final String? body;
  final double? rating;

  factory Testimonial.fromJson(Map<String, dynamic> json) {
    return Testimonial(
      id: json['id'] as int?,
      title: json['title'] as String? ?? '',
      body: json['body'] as String?,
      rating: (json['rating'] as num?)?.toDouble(),
    );
  }
}

class JournalSection {
  const JournalSection({
    this.title,
    this.subtitle,
    this.ctaLabel,
    this.ctaUrl,
    this.items = const [],
  });

  final String? title;
  final String? subtitle;
  final String? ctaLabel;
  final String? ctaUrl;
  final List<JournalItem> items;

  factory JournalSection.fromJson(Map<String, dynamic> json) {
    return JournalSection(
      title: json['title'] as String?,
      subtitle: json['subtitle'] as String?,
      ctaLabel: json['cta_label'] as String?,
      ctaUrl: json['cta_url'] as String?,
      items: (json['items'] as List<dynamic>? ?? [])
          .whereType<Map<String, dynamic>>()
          .map(JournalItem.fromJson)
          .toList(),
    );
  }
}

class JournalItem {
  const JournalItem({
    this.id,
    required this.type,
    this.title,
    this.body,
    this.linkUrl,
    this.image,
    this.author,
    this.category,
    this.publishedAt,
  });

  final int? id;

  /// `blog` or `promo`.
  final String type;
  final String? title;
  final String? body;
  final String? linkUrl;
  final String? image;
  final String? author;
  final String? category;
  final DateTime? publishedAt;

  factory JournalItem.fromJson(Map<String, dynamic> json) {
    return JournalItem(
      id: json['id'] as int?,
      type: json['type'] as String? ?? 'blog',
      title: json['title'] as String?,
      body: json['body'] as String?,
      linkUrl: json['link_url'] as String?,
      image: json['image'] as String?,
      author: json['author'] as String?,
      category: json['category'] as String?,
      publishedAt: json['published_at'] != null ? DateTime.tryParse(json['published_at'] as String) : null,
    );
  }
}

class InstagramSection {
  const InstagramSection({this.title, this.subtitle, this.items = const []});

  final String? title;
  final String? subtitle;
  final List<Product> items;

  factory InstagramSection.fromJson(Map<String, dynamic> json) {
    return InstagramSection(
      title: json['title'] as String?,
      subtitle: json['subtitle'] as String?,
      items: (json['items'] as List<dynamic>? ?? [])
          .whereType<Map<String, dynamic>>()
          .map(Product.fromJson)
          .toList(),
    );
  }
}

class StatsSection {
  const StatsSection({
    this.title,
    this.subtitle,
    this.ctaLabel,
    this.ctaUrl,
    this.items = const [],
  });

  final String? title;
  final String? subtitle;
  final String? ctaLabel;
  final String? ctaUrl;
  final List<StatItem> items;

  factory StatsSection.fromJson(Map<String, dynamic> json) {
    return StatsSection(
      title: json['title'] as String?,
      subtitle: json['subtitle'] as String?,
      ctaLabel: json['cta_label'] as String?,
      ctaUrl: json['cta_url'] as String?,
      items: (json['items'] as List<dynamic>? ?? [])
          .whereType<Map<String, dynamic>>()
          .map(StatItem.fromJson)
          .toList(),
    );
  }
}

class StatItem {
  const StatItem({this.id, required this.value, required this.label});

  final int? id;
  final String value;
  final String label;

  factory StatItem.fromJson(Map<String, dynamic> json) {
    return StatItem(
      id: json['id'] as int?,
      value: json['value'] as String? ?? '',
      label: json['label'] as String? ?? '',
    );
  }
}

class ServiceBenefit {
  const ServiceBenefit({this.title, this.body, this.icon});

  final String? title;
  final String? body;
  final String? icon;

  factory ServiceBenefit.fromJson(Map<String, dynamic> json) {
    return ServiceBenefit(
      title: json['title'] as String?,
      body: json['body'] as String?,
      icon: json['icon'] as String?,
    );
  }
}

class FooterData {
  const FooterData({
    this.about,
    this.companyName,
    this.copyright,
    this.contactAddress,
    this.contactHours,
    this.contactEmail,
    this.contactPhone,
    this.popularSearches = const [],
    this.paymentBadges = const [],
  });

  final String? about;
  final String? companyName;
  final String? copyright;
  final String? contactAddress;
  final String? contactHours;
  final String? contactEmail;
  final String? contactPhone;
  final List<String> popularSearches;
  final List<String> paymentBadges;

  factory FooterData.fromJson(Map<String, dynamic> json) {
    List<String> list(String key) =>
        (json[key] as List<dynamic>? ?? []).map((e) => e.toString()).toList();

    return FooterData(
      about: json['footer_about'] as String?,
      companyName: json['footer_company_name'] as String?,
      copyright: json['footer_copyright'] as String?,
      contactAddress: json['contact_address'] as String?,
      contactHours: json['contact_hours'] as String?,
      contactEmail: json['contact_email'] as String?,
      contactPhone: json['contact_phone'] as String?,
      popularSearches: list('footer_popular_searches'),
      paymentBadges: list('footer_payment_badges'),
    );
  }
}