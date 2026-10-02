import 'package:flutter/material.dart';

/// Estele design system colours (from the brand spec).
///
/// Usage rules:
///  - [goldLeafGradient] is for the "ESTELE" wordmark only — never body copy.
///  - Accent/rose and gold are the two "brand" accents; ink/paper are the
///    neutral spine everything else hangs off.
abstract final class AppColors {
  // Accent / rose
  static const Color accent = Color(0xFFCB6B88);
  static const Color accentDark = Color(0xFFAD3D5F);

  // Gold
  static const Color gold = Color(0xFFD4AF37);
  static const Color goldHover = Color(0xFFB38728);
  static const Color goldLight = Color(0xFFF5E6BE);

  // Ink
  static const Color ink = Color(0xFF33302F);
  static const Color heading = Color(0xFF1F1D1D);
  static const Color muted = Color(0xFF666666);

  // Surfaces
  static const Color ivory = Color(0xFFFAF8F5);
  static const Color warmBeige = Color(0xFFF3EDE7);
  static const Color pinkSoft = Color(0xFFFBF1F4);
  static const Color greySoft = Color(0xFFF2EFEB);
  static const Color paper = Color(0xFFFFFFFF);
  static const Color deepWine = Color(0xFF2B141C);

  // Lines
  static const Color line = Color(0xFFEAE4DE);
  static const Color lineStrong = Color(0xFFD9D0C8);

  // Commerce
  static const Color price = Color(0xFF1F1D1D);
  static const Color sale = Color(0xFFAD3D5F);
  static const Color newBadge = Color(0xFFCB6B88);
  static const Color soldOut = Color(0xFF8C807B);
  static const Color star = Color(0xFFD4AF37);

  // Wordmark
  static const List<Color> goldLeafGradient = [
    Color(0xFFBF953F),
    Color(0xFFFCF6BA),
    Color(0xFFB38728),
    Color(0xFFFBF5B7),
  ];

  // Semantic
  static const Color success = Color(0xFF4A7C59);
  static const Color error = Color(0xFFB3261E);
  static const Color info = Color(0xFF33658A);
}
