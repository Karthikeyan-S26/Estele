import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import 'app_colors.dart';

/// Typography helpers for the four Estele typefaces.
///
/// Font rules from the design system:
///  - [wordmark]     — Cinzel, gold-leaf gradient, ONLY for "ESTELE" logotype
///  - [sectionTitle] — Cinzel, section headings / engraved-capital feel
///  - [editorial]    — Playfair Display, editorial headings
///  - [scriptAccent] — Allura, one scripted accent word only
///  - everything else — Plus Jakarta Sans
abstract final class AppTypography {
  // ---------------------------------------------------------------------
  // Brand wordmark — Cinzel + gold-leaf gradient. Never use for body copy.
  // ---------------------------------------------------------------------
  static TextStyle wordmark({double size = 28, FontWeight weight = FontWeight.w600}) {
    return GoogleFonts.cinzel(
      fontSize: size,
      fontWeight: weight,
      letterSpacing: 3.5,
      color: Colors.white,
    );
  }

  /// The `#BF953F → #FCF6BA → #B38728 → #FBF5B7` gold-leaf gradient used for
  /// the "ESTELE" wordmark. Kept private so callers go through [logo].
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

  /// Renders the "ESTELE" logotype with the gold-leaf gradient fill.
  static Widget logo({double fontSize = 28, bool light = false}) {
    return ShaderMask(
      shaderCallback: (bounds) => _goldLeafGradient.createShader(bounds),
      child: Text(
        'ESTELE',
        style: wordmark(size: fontSize).copyWith(color: Colors.white),
      ),
    );
  }

  // ---------------------------------------------------------------------
  // Section titles — Cinzel (engraved-capital feel)
  // ---------------------------------------------------------------------
  static TextStyle sectionTitle({
    double size = 20,
    Color color = AppColors.heading,
    FontWeight weight = FontWeight.w600,
  }) {
    return GoogleFonts.cinzel(
      fontSize: size,
      fontWeight: weight,
      letterSpacing: 2.0,
      color: color,
    );
  }

  // ---------------------------------------------------------------------
  // Editorial headings — Playfair Display
  // ---------------------------------------------------------------------
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

  // ---------------------------------------------------------------------
  // Scripted accent — Allura (ONE word per screen, never body copy)
  // ---------------------------------------------------------------------
  static TextStyle scriptAccent({double size = 34, Color color = AppColors.accent}) {
    return GoogleFonts.allura(
      fontSize: size,
      color: color,
    );
  }

  // ---------------------------------------------------------------------
  // Body + UI — Plus Jakarta Sans
  // ---------------------------------------------------------------------
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
    return GoogleFonts.plusJakartaSans(fontSize: size, fontWeight: weight, color: color);
  }

  static TextStyle bodySmall({
    double size = 12,
    Color color = AppColors.muted,
    FontWeight weight = FontWeight.w400,
  }) {
    return GoogleFonts.plusJakartaSans(fontSize: size, fontWeight: weight, color: color);
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
    return GoogleFonts.plusJakartaSans(fontSize: size, fontWeight: weight, color: color);
  }

  static TextStyle salePrice({
    double size = 15,
    Color color = AppColors.sale,
    FontWeight weight = FontWeight.w700,
  }) {
    return GoogleFonts.plusJakartaSans(fontSize: size, fontWeight: weight, color: color);
  }
}