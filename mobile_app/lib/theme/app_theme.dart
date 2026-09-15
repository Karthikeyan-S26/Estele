import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import 'app_colors.dart';

/// The Flutter Material theme for Estele — flat, warm, editorial jewellery
/// brand feel. Sharp/low-radius corners, thin lines, no heavy shadows.
abstract final class AppTheme {
  static const BorderRadius _defaultRadius = BorderRadius.all(
    Radius.circular(2),
  );

  static ThemeData get light {
    final base = ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(
        seedColor: AppColors.accent,
        primary: AppColors.accent,
        secondary: AppColors.gold,
        surface: AppColors.paper,
      ),
      scaffoldBackgroundColor: AppColors.ivory,
      fontFamily: 'Plus Jakarta Sans',
    );

    final jakarta = GoogleFonts.plusJakartaSansTextTheme();

    return base.copyWith(
      scaffoldBackgroundColor: AppColors.ivory,
      colorScheme: base.colorScheme.copyWith(
        primary: AppColors.accent,
        onPrimary: Colors.white,
        secondary: AppColors.gold,
        onSecondary: AppColors.heading,
        surface: AppColors.paper,
        onSurface: AppColors.ink,
        error: AppColors.error,
      ),
      textTheme: jakarta.copyWith(
        displayLarge: jakarta.displayLarge?.copyWith(color: AppColors.heading),
        displayMedium: jakarta.displayMedium?.copyWith(
          color: AppColors.heading,
        ),
        displaySmall: jakarta.displaySmall?.copyWith(color: AppColors.heading),
        headlineLarge: jakarta.headlineLarge?.copyWith(
          color: AppColors.heading,
        ),
        headlineMedium: jakarta.headlineMedium?.copyWith(
          color: AppColors.heading,
        ),
        headlineSmall: jakarta.headlineSmall?.copyWith(
          color: AppColors.heading,
        ),
        titleLarge: jakarta.titleLarge?.copyWith(
          color: AppColors.heading,
          fontWeight: FontWeight.w600,
        ),
        titleMedium: jakarta.titleMedium?.copyWith(
          color: AppColors.ink,
          fontWeight: FontWeight.w600,
        ),
        titleSmall: jakarta.titleSmall?.copyWith(
          color: AppColors.ink,
          fontWeight: FontWeight.w600,
        ),
        bodyLarge: jakarta.bodyLarge?.copyWith(color: AppColors.ink),
        bodyMedium: jakarta.bodyMedium?.copyWith(color: AppColors.ink),
        bodySmall: jakarta.bodySmall?.copyWith(color: AppColors.muted),
        labelLarge: jakarta.labelLarge?.copyWith(color: AppColors.ink),
      ),
      appBarTheme: AppBarTheme(
        backgroundColor: AppColors.ivory,
        foregroundColor: AppColors.heading,
        elevation: 0,
        scrolledUnderElevation: 0,
        surfaceTintColor: Colors.transparent,
        centerTitle: false,
        titleTextStyle: jakarta.titleLarge?.copyWith(
          color: AppColors.heading,
          fontWeight: FontWeight.w600,
          letterSpacing: 0.2,
        ),
      ),
      dividerTheme: const DividerThemeData(color: AppColors.line, thickness: 1),
      cardTheme: const CardThemeData(
        color: AppColors.paper,
        elevation: 0,
        margin: EdgeInsets.zero,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.all(Radius.circular(4)),
          side: BorderSide(color: AppColors.line),
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.accent,
          foregroundColor: Colors.white,
          disabledBackgroundColor: AppColors.greySoft,
          disabledForegroundColor: AppColors.muted,
          elevation: 0,
          minimumSize: const Size(48, 48),
          shape: RoundedRectangleBorder(borderRadius: _defaultRadius),
          textStyle: jakarta.labelLarge?.copyWith(
            fontWeight: FontWeight.w600,
            color: Colors.white,
            fontSize: 14,
          ),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: AppColors.ink,
          side: const BorderSide(color: AppColors.lineStrong),
          minimumSize: const Size(48, 48),
          shape: RoundedRectangleBorder(borderRadius: _defaultRadius),
          textStyle: jakarta.labelLarge?.copyWith(
            fontWeight: FontWeight.w600,
            fontSize: 14,
          ),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: AppColors.accentDark,
          textStyle: jakarta.labelLarge?.copyWith(
            fontWeight: FontWeight.w600,
            fontSize: 14,
          ),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.paper,
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 14,
          vertical: 14,
        ),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(2),
          borderSide: const BorderSide(color: AppColors.line),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(2),
          borderSide: const BorderSide(color: AppColors.line),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(2),
          borderSide: const BorderSide(color: AppColors.accent, width: 1.4),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(2),
          borderSide: const BorderSide(color: AppColors.error),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(2),
          borderSide: const BorderSide(color: AppColors.error, width: 1.4),
        ),
        labelStyle: jakarta.bodyMedium?.copyWith(color: AppColors.muted),
        hintStyle: jakarta.bodyMedium?.copyWith(color: AppColors.muted),
      ),
      checkboxTheme: CheckboxThemeData(
        fillColor: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) {
            return AppColors.accent;
          }
          return Colors.transparent;
        }),
        side: const BorderSide(color: AppColors.lineStrong),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(2)),
      ),
      radioTheme: RadioThemeData(
        fillColor: WidgetStateProperty.resolveWith(
          (states) => states.contains(WidgetState.selected)
              ? AppColors.accent
              : AppColors.muted,
        ),
      ),
      bottomNavigationBarTheme: const BottomNavigationBarThemeData(
        backgroundColor: AppColors.paper,
        selectedItemColor: AppColors.accentDark,
        unselectedItemColor: AppColors.muted,
        type: BottomNavigationBarType.fixed,
        elevation: 0,
        selectedLabelStyle: TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w600,
        ),
        unselectedLabelStyle: TextStyle(fontSize: 11),
      ),
      chipTheme: ChipThemeData(
        backgroundColor: AppColors.paper,
        selectedColor: AppColors.pinkSoft,
        side: const BorderSide(color: AppColors.line),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(999)),
        labelStyle: jakarta.bodyMedium,
      ),
      snackBarTheme: SnackBarThemeData(
        backgroundColor: AppColors.heading,
        contentTextStyle: jakarta.bodyMedium?.copyWith(color: Colors.white),
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(4)),
      ),
      bottomSheetTheme: const BottomSheetThemeData(
        backgroundColor: AppColors.paper,
        surfaceTintColor: Colors.transparent,
        showDragHandle: true,
      ),
      dialogTheme: DialogThemeData(
        backgroundColor: AppColors.paper,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)),
        titleTextStyle: jakarta.titleLarge?.copyWith(color: AppColors.heading),
      ),
      progressIndicatorTheme: const ProgressIndicatorThemeData(
        color: AppColors.accent,
        linearTrackColor: AppColors.pinkSoft,
      ),
    );
  }
}
