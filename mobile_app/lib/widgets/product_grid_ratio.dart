/// Responsive cell ratio for the two-column product grids across the app.
///
/// [compact] = true → horizontal strips / wishlist / trending where
/// ProductCard shows a 4:5 image with no card border and no CTA button.
///
/// [compact] = false (default) → full web-style card with a square (1:1)
/// image inside an inset frame plus the "Add to cart" CTA.  The card is
/// taller, so the ratio is smaller.
double productGridRatio(
  double viewportWidth, {
  double horizontalPadding = 16,
  double crossAxisSpacing = 10,
  bool compact = false,
  double extraInfoHeight = 0,
}) {
  final cellWidth =
      (viewportWidth - horizontalPadding * 2 - crossAxisSpacing) / 2;
  if (cellWidth <= 0) return 0.62;

  if (compact) {
    final imageHeight = cellWidth * (4 / 3);
    final cellHeight =
        imageHeight + 8 + (extraInfoHeight > 0 ? extraInfoHeight : 74);
    return cellWidth / cellHeight;
  }

  // Square image (1:1) + 8px top/bottom inset padding + title/price block +
  // spacer + CTA button.  Generous so long names/CTAs never overflow.
  const infoBlockHeight = 60; // 2-line title + price row + gaps
  const ctaHeight = 54; // button + top margin
  final cellHeight =
      cellWidth + 16 + infoBlockHeight + ctaHeight + extraInfoHeight;
  return cellWidth / cellHeight;
}
