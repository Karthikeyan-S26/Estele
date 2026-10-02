import 'dart:async';

import 'package:flutter/material.dart';

import '../../../theme/app_colors.dart';

/// Announcement bar — mirrors `app.blade.php` lines 56-67:
///   bg-announce (accentDark #AD3D5F), white 11px font-medium uppercase
///   tracking-[0.12em], centered, rotating with 500 ms fade.
///   Falls back to the static "Free Express Shipping …" when [messages] is
///   empty (same fallback as the Blade template).
class PromoBar extends StatefulWidget {
  const PromoBar({super.key, required this.messages});

  final List<String> messages;

  static const _fallback =
      'Free Express Shipping on Orders Above ₹1,499 · Use Code ESTELE50 for Flat 50% Off';

  /// Mirrors the Blade fallback, where the code is a separate
  /// `<span class="font-semibold text-gold">` inside the white line.
  /// The whole line is uppercased by the blade's `uppercase` class.
  static List<TextSpan>? _fallbackSpans() {
    final text = _fallback.toUpperCase();
    const code = 'ESTELE50';
    final idx = text.indexOf(code);
    if (idx < 0) return null;
    return [
      TextSpan(text: text.substring(0, idx)),
      const TextSpan(
        text: code,
        style: TextStyle(
          color: AppColors.gold, // text-gold
          fontWeight: FontWeight.w600, // font-semibold
        ),
      ),
      TextSpan(text: text.substring(idx + code.length)),
    ];
  }

  @override
  State<PromoBar> createState() => _PromoBarState();
}

class _PromoBarState extends State<PromoBar> {
  int _index = 0;
  Timer? _timer;

  List<String> get _items => widget.messages.isNotEmpty
      ? widget.messages.map((m) => m.toUpperCase()).toList()
      : [PromoBar._fallback];

  @override
  void initState() {
    super.initState();
    if (_items.length > 1) {
      _timer = Timer.periodic(const Duration(seconds: 3), (_) {
        if (mounted) setState(() => _index = (_index + 1) % _items.length);
      });
    }
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      color: AppColors.accentDark, // bg-announce = #AD3D5F
      width: double.infinity,
      padding: const EdgeInsets.symmetric(vertical: 8), // py-2 = 8px top/bottom
      alignment: Alignment.center,
      child: SizedBox(
        height: 16, // h-4 in Blade (holds one line of text)
        child: AnimatedSwitcher(
          duration: const Duration(milliseconds: 500), // transition-opacity-500
          child: Text.rich(
            TextSpan(
              // The Blade only uses the special ESTELE50 fallback when there are
              // no CMS messages; otherwise it shows the message verbatim.
              children: widget.messages.isEmpty
                  ? (PromoBar._fallbackSpans() ??
                      [TextSpan(text: _items[_index % _items.length])])
                  : [TextSpan(text: _items[_index % _items.length])],
            ),
            key: ValueKey(_index),
            textAlign: TextAlign.center,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 11, // text-[11px]
              fontWeight: FontWeight.w500, // font-medium
              letterSpacing: 11 * 0.12, // tracking-[0.12em] at 11px ≈ 1.32
              height: 1.0,
            ),
          ),
        ),
      ),
    );
  }
}
