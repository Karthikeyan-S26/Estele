import 'package:flutter/material.dart';

import '../../data/repositories/catalog_repository.dart';
import '../../models/home_data.dart';
import '../../models/product.dart';
import '../../utils/app_link.dart';
import '../../widgets/load_state.dart';
import 'home/benefits_block.dart';
import 'home/budget_tiles.dart';
import 'home/category_strip.dart';
import 'home/celebrity_strip.dart';
import 'home/collection_banner.dart';
import 'home/collections_grid.dart';
import 'home/faq_block.dart';
import 'home/footer_block.dart';
import 'home/hero_carousel.dart';
import 'home/instagram_block.dart';
import 'home/journal_block.dart';
import 'home/promo_bar.dart';
import 'home/product_strip.dart';
import 'home/stats_block.dart';
import 'home/testimonials_block.dart';

/// Customer home — mirrors the Estele web homepage top to bottom, composed
/// entirely from the structured `/api/home` payload. No fake/merged data:
/// every section is a real CMS block, and a section is skipped when the
/// backend returns nothing for it.
class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  HomeData? _data;
  String? _error;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    CatalogRepository.clearCaches();
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final data = await CatalogRepository.home();
      if (mounted) {
        setState(() {
          _data = data;
          _loading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = e.toString();
          _loading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading && _data == null) return const HomeShimmer();
    if (_error != null && _data == null) {
      return LoadState.error(message: _error!, onRetry: _load);
    }

    final data = _data!;

    return RefreshIndicator(
      onRefresh: _load,
      child: CustomScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        slivers: [
          SliverToBoxAdapter(child: PromoBar(messages: data.promo)),

          SliverToBoxAdapter(child: HeroCarousel(banners: data.heroBanners)),

          SliverToBoxAdapter(child: CategoryStrip(categories: data.categories)),

          if (data.collectionBanners.isNotEmpty)
            SliverToBoxAdapter(
              child: CollectionBannerBlock(
                banner: data.collectionBanners.first,
              ),
            ),

          SliverToBoxAdapter(
            child: ProductStrip(
              scriptWord: data.trendingEyebrow,
              title: data.trendingTitle,
              products: data.trendingProducts,
              onViewAll: () => Navigator.of(context).pushNamed('/trending'),
              viewAllLabel: 'View all',
            ),
          ),

          SliverToBoxAdapter(
            child: CollectionsGrid(collections: data.collections),
          ),

          SliverToBoxAdapter(child: BudgetTiles(tiers: data.priceTiers)),

          SliverToBoxAdapter(
            child: ProductStrip(
              scriptWord: data.newArrivalsEyebrow,
              title: data.newArrivalsTitle,
              products: data.newArrivals,
              onViewAll: data.newArrivalsCta == null
                  ? null
                  : () => resolveAppLink(context, data.newArrivalsCta),
              viewAllLabel: 'View all',
            ),
          ),

          // Bestsellers — hidden when it would be a byte-for-byte repeat of
          // the trending strip (the web falls back to the same source set).
          if (_distinctFrom(data.bestsellers, data.trendingProducts))
            SliverToBoxAdapter(
              child: ProductStrip(
                scriptWord: data.bestsellersEyebrow,
                title: data.bestsellersTitle,
                products: data.bestsellers,
                onViewAll: data.bestsellersCta == null
                    ? null
                    : () => resolveAppLink(context, data.bestsellersCta),
                viewAllLabel: 'View all',
              ),
            ),

          SliverToBoxAdapter(
            child: CelebrityStrip(celebrities: data.celebrities),
          ),

          SliverToBoxAdapter(child: BenefitsBlock(benefits: data.benefits)),

          SliverToBoxAdapter(
            child: TestimonialsBlock(testimonials: data.testimonials),
          ),

          if (data.journal != null)
            SliverToBoxAdapter(child: JournalBlock(journal: data.journal!)),

          if (data.instagram != null)
            SliverToBoxAdapter(child: InstagramBlock(section: data.instagram!)),

          if (data.stats != null)
            SliverToBoxAdapter(child: StatsBlock(stats: data.stats!)),

          SliverToBoxAdapter(child: FaqBlock(faqs: data.faqs)),

          if (data.footer != null)
            SliverToBoxAdapter(
              child: FooterBlock(footer: data.footer!, services: data.services),
            ),
        ],
      ),
    );
  }
}

bool _distinctFrom(List<Product> a, List<Product> b) {
  if (a.isEmpty || b.isEmpty) return true;
  final idsA = a.map((p) => p.id).toSet();
  final idsB = b.map((p) => p.id).toSet();
  if (idsA.length != idsB.length) return true;
  return !idsA.containsAll(idsB);
}
