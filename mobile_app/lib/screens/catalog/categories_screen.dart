import 'package:flutter/material.dart';

import '../../data/repositories/catalog_repository.dart';
import '../../models/category.dart';
import '../../models/collection.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_typography.dart';
import '../../widgets/app_image.dart';
import '../../widgets/load_state.dart';
import '../../widgets/section_header.dart';
import 'category_products_screen.dart';

/// Categories tab — a grid of product categories plus a collections rail on
/// top. Tapping a category opens its product listing.
class CategoriesScreen extends StatefulWidget {
  const CategoriesScreen({super.key});

  @override
  State<CategoriesScreen> createState() => _CategoriesScreenState();
}

class _CategoriesScreenState extends State<CategoriesScreen> {
  List<Category>? _categories;
  List<Collection>? _collections;
  bool _loading = true;
  bool _failed = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _failed = false;
    });
    try {
      final results = await Future.wait([
        CatalogRepository.categories(),
        CatalogRepository.collections(),
      ]);
      if (mounted) {
        setState(() {
          _categories = results[0] as List<Category>;
          _collections = results[1] as List<Collection>;
          _loading = false;
        });
      }
    } catch (_) {
      if (mounted)
        setState(() {
          _failed = true;
          _loading = false;
        });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const LoadState.loading();
    if (_failed && _categories == null) {
      return LoadState.error(
        message: 'Could not load categories',
        onRetry: _load,
      );
    }

    final categories = _categories ?? [];
    final collections = _collections ?? [];

    return RefreshIndicator(
      onRefresh: _load,
      child: CustomScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        slivers: [
          const SliverToBoxAdapter(child: SizedBox.shrink()),
          // Collections rail
          if (collections.isNotEmpty)
            SliverToBoxAdapter(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
                    child: SectionHeader(
                      title: 'Collections',
                      scriptWord: 'curated',
                      onViewAll: null,
                    ),
                  ),
                  SizedBox(
                    height: 150,
                    child: ListView.builder(
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      scrollDirection: Axis.horizontal,
                      itemCount: collections.length,
                      itemBuilder: (context, i) {
                        final collection = collections[i];
                        return _WideCard(
                          image: collection.imageUrl,
                          title: collection.name,
                          count: collection.productCount,
                          onTap: () => Navigator.of(context).push(
                            MaterialPageRoute(
                              builder: (_) => CategoryProductsScreen(
                                title: collection.name,
                                categorySlug: collection.slug,
                                isCollection: true,
                              ),
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                ],
              ),
            ),
          // Category grid
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
            sliver: SliverGrid(
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2,
                mainAxisSpacing: 12,
                crossAxisSpacing: 12,
                childAspectRatio: 0.9,
              ),
              delegate: SliverChildBuilderDelegate((context, i) {
                final category = categories[i];
                return _CategoryTile(
                  name: category.name,
                  count: category.productCount,
                  image: category.imageUrl,
                  onTap: () => Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => CategoryProductsScreen(
                        title: category.name,
                        categorySlug: category.slug,
                      ),
                    ),
                  ),
                );
              }, childCount: categories.length),
            ),
          ),
        ],
      ),
    );
  }
}

class _CategoryTile extends StatelessWidget {
  const _CategoryTile({
    required this.name,
    required this.count,
    this.image,
    required this.onTap,
  });

  final String name;
  final int count;
  final String? image;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(4),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: AppImage(url: image, borderRadius: BorderRadius.circular(4)),
          ),
          const SizedBox(height: 8),
          Text(
            name,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: AppTypography.body(size: 13.5),
          ),
          Text(
            '$count pieces',
            style: AppTypography.bodySmall(size: 11, color: AppColors.muted),
          ),
        ],
      ),
    );
  }
}

class _WideCard extends StatelessWidget {
  const _WideCard({
    required this.image,
    required this.title,
    required this.count,
    required this.onTap,
  });

  final String? image;
  final String title;
  final int count;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 220,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(4),
        child: Stack(
          fit: StackFit.expand,
          children: [
            AppImage(url: image, borderRadius: BorderRadius.circular(4)),
            Container(
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(4),
                gradient: LinearGradient(
                  begin: Alignment.bottomCenter,
                  end: Alignment.topCenter,
                  colors: [
                    AppColors.deepWine.withValues(alpha: 0.72),
                    Colors.transparent,
                  ],
                ),
              ),
            ),
            Positioned(
              left: 12,
              right: 12,
              bottom: 10,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: AppTypography.sectionTitle(
                      size: 15,
                      color: Colors.white,
                    ),
                  ),
                  Text(
                    'View collection  →',
                    style: AppTypography.bodySmall(
                      size: 11,
                      color: Colors.white70,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
