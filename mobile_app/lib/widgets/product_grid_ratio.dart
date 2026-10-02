/// Responsive cell ratio for the two-column product grids across the app.
///
/// [compact] = true → horizontal strips / wishlist / trending where
/// ProductCard shows a 4:5 image with no card border and no CTA button.
///
/// [compact] = false (default) → full web-style card with a square (1:1)
/// image inside an inset frame plus the bottom-docked Buy Now + Add to
/// Bag CTA row, exactly like the website `x-product-card` form.
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
    // 2-line title + gap + price row + gap + 36px CTA row.
    final cellHeight =
        imageHeight + 8 + (extraInfoHeight > 0 ? extraInfoHeight : 100);
    return cellWidth / cellHeight;
  }

  // Square image (1:1) + 8px top/bottom inset padding + title/price block +
  // bottom-docked CTA row.  Sized to the bounded content so grid heights
  // stay consistent with no squeeze and no blank gap.
  const infoBlockHeight = 64; // 2-line title + price row + gaps
  const ctaHeight = 44; // 8px gap + 36px CTA row
  final cellHeight =
      cellWidth + 16 + infoBlockHeight + ctaHeight + extraInfoHeight;
  return cellWidth / cellHeight;
}
