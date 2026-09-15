import 'package:intl/intl.dart';

/// Indian Rupee formatting — `en_IN` grouping (₹1,23,456).
NumberFormat get inrFormat =>
    NumberFormat.currency(symbol: '₹', locale: 'en_IN', decimalDigits: 0);

/// Formats an amount as ₹X — the app's money display everywhere.
String formatINR(num value) => inrFormat.format(value);

/// Compact formatter for very large numbers (₹1.2L) — wishlist/misc labels.
String formatINRCompact(num value) {
  if (value >= 10000000) {
    return '₹${(value / 10000000).toStringAsFixed(1)} Cr';
  }
  if (value >= 100000) {
    return '₹${(value / 100000).toStringAsFixed(1)}L';
  }
  if (value >= 1000) {
    return '₹${(value / 1000).toStringAsFixed(1)}K';
  }
  return formatINR(value);
}

/// Strips a `.0` decimal residue from rupee strings occasionally left by
/// NumberFormat when the input has no paise component.
String cleanINR(num value) {
  final s = inrFormat.format(value);
  return s.endsWith('.00') ? s.substring(0, s.length - 3) : s;
}
