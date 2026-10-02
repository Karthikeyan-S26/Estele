import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../theme/app_colors.dart';
import '../theme/app_typography.dart';

/// Programmatic handle for [OtpCodeField] — lets the parent clear the boxes
/// (e.g. after an invalid code is rejected) without owning the internals.
class OtpCodeFieldController {
  final TextEditingController inner = TextEditingController();

  String get code => inner.text;

  void clear() => inner.clear();

  void dispose() => inner.dispose();
}

/// Website-parity 6-box OTP input: digits auto-advance focus between boxes,
/// and the moment the sixth digit is entered [onCompleted] fires — the screen
/// starts verification immediately, exactly like the site.
///
/// Single invisible [TextField] sits over the six boxes (so paste of a full
/// code just works); each box renders one digit, and the focused box gets a
/// brand border.
class OtpCodeField extends StatefulWidget {
  const OtpCodeField({
    super.key,
    this.onCompleted,
    this.controller,
    this.boxSize = 48,
    this.disabled = false,
    this.autofocus = true,
  });

  /// Called with the full 6-digit code the moment the sixth digit lands.
  final ValueChanged<String>? onCompleted;

  /// Optional external handle; when omitted the field owns its controller.
  final OtpCodeFieldController? controller;

  final double boxSize;
  final bool disabled;
  final bool autofocus;

  @override
  State<OtpCodeField> createState() => _OtpCodeFieldState();
}

class _OtpCodeFieldState extends State<OtpCodeField> {
  static const int _length = 6;
  static const double _gap = 8;

  late final TextEditingController _controller;
  late final FocusNode _focus;
  bool _fired = false;

  @override
  void initState() {
    super.initState();
    _controller = widget.controller?.inner ?? TextEditingController();
    _focus = FocusNode();
    _controller.addListener(_syncDigitsAndFire);
  }

  @override
  void dispose() {
    _focus.dispose();
    if (widget.controller == null) _controller.dispose();
    super.dispose();
  }

  /// Keep the stored value a clean ≤6 char digit string (paste of "ABC123456"
  /// arrives as one blob), then fire [onCompleted] once when uncapped at 6.
  void _syncDigitsAndFire() {
    final digits = _controller.text.replaceAll(RegExp(r'\D'), '');
    var next = digits;
    if (digits.length > _length) next = digits.substring(0, _length);
    if (next != _controller.text) {
      _controller.value = TextEditingValue(
        text: next,
        selection: TextSelection.collapsed(offset: next.length),
        composing: TextRange.empty,
      );
      // Guard: replacing the value re-enters this listener; recompute after.
    }
    final effective = _controller.text;
    if (effective.length == _length && !_fired) {
      _fired = true;
      widget.onCompleted?.call(effective);
    } else if (effective.length < _length) {
      _fired = false;
    }
    if (mounted) setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () => _focus.requestFocus(),
      child: SizedBox(
        height: widget.boxSize,
        child: Stack(
          fit: StackFit.expand,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                for (var i = 0; i < _length; i++) ...[
                  if (i > 0) const SizedBox(width: _gap),
                  _box(i),
                ],
              ],
            ),
            // Transparent input layered over the boxes — reads digits only,
            // accepts pasted codes, caret hidden (the highlighted box shows
            // where the next digit lands).
            Opacity(
              opacity: 0,
              child: TextField(
                controller: _controller,
                focusNode: _focus,
                enabled: !widget.disabled,
                autofocus: widget.autofocus,
                keyboardType: TextInputType.number,
                textInputAction: TextInputAction.done,
                inputFormatters: [
                  FilteringTextInputFormatter.digitsOnly,
                  LengthLimitingTextInputFormatter(6),
                ],
                maxLength: 6,
                textAlign: TextAlign.center,
                showCursor: false,
                decoration: const InputDecoration(
                  border: InputBorder.none,
                  counterText: '',
                ),
                style: const TextStyle(color: Colors.transparent),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _box(int i) {
    final filled = i < _controller.text.length;
    final active = _focus.hasFocus && !widget.disabled && !filled;
    final digit = filled ? _controller.text[i] : '';

    return Container(
      width: widget.boxSize,
      height: widget.boxSize,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        color: AppColors.paper,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(
          color: active ? AppColors.accent : AppColors.lineStrong,
          width: active ? 1.6 : 1,
        ),
      ),
      child: Text(
        digit,
        style: AppTypography.body(
          size: 20,
          weight: FontWeight.w600,
          color: AppColors.heading,
        ),
      ),
    );
  }
}