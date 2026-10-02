import 'package:flutter/material.dart';

import '../../data/repositories/catalog_repository.dart';
import 'product_grid_screen.dart';

/// Listing screen for a single category or collection's products — the
/// shared loader wires category/collection slugs into [ProductGridScreen].
class CategoryProductsScreen extends StatelessWidget {
  const CategoryProductsScreen({
    super.key,
    required this.title,
    required this.categorySlug,
    this.isCollection = false,
  });

  final String title;
  final String categorySlug;
  final bool isCollection;

  @override
  Widget build(BuildContext context) {
    return ProductGridScreen(
      title: title,
      loader:
          ({
            required String sort,
            String? minPrice,
            String? maxPrice,
            required bool inStock,
            required int page,
            required int perPage,
          }) {
            if (isCollection) {
              return CatalogRepository.collectionProducts(
                categorySlug,
                sort: sort,
                page: page,
                perPage: perPage,
              );
            }
            return CatalogRepository.categoryProducts(
              categorySlug,
              sort: sort,
              page: page,
              perPage: perPage,
            );
          },
    );
  }
}
