import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import 'app_colors.dart';

/// Estele type-face helpers.
///
/// Font system (matches web `app.blade.php` line 39-42):
///  - **Cinzel** (w500-w700) — wordmark + section headings
///  - **Playfair Display** (w400-w600) — editorial / product titles
///  - **Allura** — one-script accent word only
///  - **Plus Jakarta Sans** (w300-w700) — everything else
///
/// Tracking conventions (em values from web, converted to logical px):
///  - wordmark:  `0.08em` (header: 0.08 × 20 = 1.6)
///  - eyebrow:   `0.22em`
///  - nav label: `0.3px` (absolute, not em)
///  - announcement: `0.12em`
abstract final class AppTypography {
  // ─────────────────────────────────────────────────────────────────────
  // Wordmark — Cinzel, dark by default, NO gradient (gradient is footer/
  // brand-story only).
  // ─────────────────────────────────────────────────────────────────────

  /// Dark wordmark TextStyle (Cinzel w600, heading colour, 0.08em tracking).
  static TextStyle wordmark({
    double size = 20,
    Color color = AppColors.heading,
    FontWeight weight = FontWeight.w600,
  }) {
    return GoogleFonts.cinzel(
      fontSize: size,
      fontWeight: weight,
      letterSpacing: size * 0.08,
      color: color,
    );
  }

  /// Renders the brand name ("Estele") in dark Cinzel 600 with 0.08em
  /// tracking — the exact header wordmark (NOT the gold-leaf footer/brand
  /// story variant).
  static Widget logo({double fontSize = 20, Color? color}) {
    return Text(
      'Estele',
      style: wordmark(size: fontSize, color: color ?? AppColors.heading),
    );
  }

  // ─────────────────────────────────────────────────────────────────────
  // Gold-leaf gradient — reserved for the brand-story / footer logotype.
  // ─────────────────────────────────────────────────────────────────────

  static final LinearGradient _goldLeafGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [
      Color(0xFFBF953F),
      Color(0xFFFCF6BA),
      Color(0xFFB38728),
      Color(0xFFFBF5B7),
    ],
    stops: const [0.0, 0.3, 0.62, 1.0],
  );

  /// Gold-leaf gradient "Estele" (brand-story / footer lockup).
  static Widget goldLeafLogo({double fontSize = 28}) {
    return ShaderMask(
      shaderCallback: (bounds) => _goldLeafGradient.createShader(bounds),
      child: Text(
        'Estele',
        style: wordmark(size: fontSize, color: Colors.white),
      ),
    );
  }

  // ─────────────────────────────────────────────────────────────────────
  // Section titles — Cinzel
  // ─────────────────────────────────────────────────────────────────────
  static TextStyle sectionTitle({
    double size = 17,
    Color color = AppColors.heading,
    FontWeight weight = FontWeight.w600,
  }) {
    return GoogleFonts.cinzel(
      fontSize: size,
      fontWeight: weight,
      letterSpacing: size * 0.08,
      color: color,
    );
  }

  // ─────────────────────────────────────────────────────────────────────
  // Eyebrow — PJS 600, 10.5, uppercase, 0.22em, accent
  // ─────────────────────────────────────────────────────────────────────
  static TextStyle eyebrow({
    double size = 10.5,
    Color color = AppColors.accent,
  }) {
    return GoogleFonts.plusJakartaSans(
      fontSize: size,
      fontWeight: FontWeight.w600,
      letterSpacing: size * 0.22,
      color: color,
    );
  }

  // ─────────────────────────────────────────────────────────────────────
  // Editorial headings — Playfair Display
  // ─────────────────────────────────────────────────────────────────────
  static TextStyle editorial({
    double size = 26,
    Color color = AppColors.heading,
    FontWeight weight = FontWeight.w600,
    double height = 1.2,
  }) {
    return GoogleFonts.playfairDisplay(
      fontSize: size,
      fontWeight: weight,
      color: color,
      height: height,
    );
  }

  // ─────────────────────────────────────────────────────────────────────
  // Script accent — Allura (ONE word per screen, never body copy)
  // ─────────────────────────────────────────────────────────────────────
  static TextStyle scriptAccent({
    double size = 34,
    Color color = AppColors.accent,
  }) {
    return GoogleFonts.allura(fontSize: size, color: color);
  }

  // ─────────────────────────────────────────────────────────────────────
  // Body + UI — Plus Jakarta Sans
  // ─────────────────────────────────────────────────────────────────────
  static TextStyle body({
    double size = 14,
    Color color = AppColors.ink,
    FontWeight weight = FontWeight.w400,
    double height = 1.5,
  }) {
    return GoogleFonts.plusJakartaSans(
      fontSize: size,
      fontWeight: weight,
      color: color,
      height: height,
    );
  }

  static TextStyle bodyMedium({
    double size = 14,
    Color color = AppColors.ink,
    FontWeight weight = FontWeight.w500,
  }) {
    return GoogleFonts.plusJakartaSans(
      fontSize: size,
      fontWeight: weight,
      color: color,
    );
  }

  static TextStyle bodySmall({
    double size = 12,
    Color color = AppColors.muted,
    FontWeight weight = FontWeight.w400,
  }) {
    return GoogleFonts.plusJakartaSans(
      fontSize: size,
      fontWeight: weight,
      color: color,
    );
  }

  static TextStyle button({
    double size = 14,
    Color color = Colors.white,
    FontWeight weight = FontWeight.w600,
    double letterSpacing = 0.5,
  }) {
    return GoogleFonts.plusJakartaSans(
      fontSize: size,
      fontWeight: weight,
      color: color,
      letterSpacing: letterSpacing,
    );
  }

  static TextStyle label({
    double size = 12,
    Color color = AppColors.muted,
    FontWeight weight = FontWeight.w600,
    double letterSpacing = 1.2,
  }) {
    return GoogleFonts.plusJakartaSans(
      fontSize: size,
      fontWeight: weight,
      color: color,
      letterSpacing: letterSpacing,
    );
  }

  static TextStyle price({
    double size = 15,
    Color color = AppColors.price,
    FontWeight weight = FontWeight.w700,
  }) {
    return GoogleFonts.plusJakartaSans(
      fontSize: size,
      fontWeight: weight,
      color: color,
    );
  }

  static TextStyle salePrice({
    double size = 15,
    Color color = AppColors.sale,
    FontWeight weight = FontWeight.w700,
  }) {
    return GoogleFonts.plusJakartaSans(
      fontSize: size,
      fontWeight: weight,
      color: color,
    );
  }
}
