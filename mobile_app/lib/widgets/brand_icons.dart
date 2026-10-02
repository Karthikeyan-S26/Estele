import 'dart:math' as math;

import 'package:flutter/material.dart';

/// Exact Estele web icons — the same lucide/feather SVG path strings used in
/// `backend/resources/views/layouts/app.blade.php`.
///
/// Web source (stroke-width 1.5 → 1.6 depending on context):
///   header:  h-5 w-5 (20px), stroke-width 1.6
///   bottom:  h-5 w-5 (20px), stroke-width 1.5
///   badges:  h-4 min-w-4 rounded-lg bg-accent text-[10px]
enum BrandIconName { home, grid, heart, bag, user, search, menu }

class BrandIcon extends StatelessWidget {
  const BrandIcon({
    super.key,
    required this.icon,
    this.size = 20,
    this.strokeWidth = 1.6,
    this.color,
  });

  final BrandIconName icon;
  final double size;
  final double strokeWidth;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    final c = color ?? DefaultTextStyle.of(context).style.color ?? Colors.black;
    return CustomPaint(
      size: Size(size, size),
      painter: _BrandIconPainter(
        icon: icon,
        color: c,
        strokeWidth: strokeWidth,
      ),
    );
  }
}

class _BrandIconPainter extends CustomPainter {
  const _BrandIconPainter({
    required this.icon,
    required this.color,
    required this.strokeWidth,
  });

  final BrandIconName icon;
  final Color color;
  final double strokeWidth;

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = color
      ..style = PaintingStyle.stroke
      ..strokeWidth = strokeWidth
      ..strokeCap = StrokeCap.round
      ..strokeJoin = StrokeJoin.round;

    canvas.save();
    canvas.scale(size.width / 24.0, size.height / 24.0);

    switch (icon) {
      case BrandIconName.home:
        canvas.drawPath(
          _parseSvg(
            'M3 10l9-7 9 7v10a2 2 0 0 1-2 2h-4v-7H9v7H5a2 2 0 0 1-2-2z',
          ),
          paint,
        );
      case BrandIconName.grid:
        final r = RRect.fromLTRBR(3, 3, 10, 10, const Radius.circular(1.5));
        final path = Path()
          ..addRRect(r)
          ..addRRect(r.shift(const Offset(11, 0)))
          ..addRRect(r.shift(const Offset(0, 11)))
          ..addRRect(r.shift(const Offset(11, 11)));
        canvas.drawPath(path, paint);
      case BrandIconName.heart:
        canvas.drawPath(
          _parseSvg(
            'M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21.2l7.7-7.7 1.1-1.1a5.5 5.5 0 0 0 0-7.8z',
          ),
          paint,
        );
      case BrandIconName.bag:
        final path = Path()
          ..addPath(
            _parseSvg('M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z'),
            Offset.zero,
          )
          ..addPath(_parseSvg('M3 6h18'), Offset.zero)
          ..addPath(_parseSvg('M16 10a4 4 0 0 1-8 0'), Offset.zero);
        canvas.drawPath(path, paint);
      case BrandIconName.user:
        canvas.drawOval(
          Rect.fromCircle(center: const Offset(12, 8), radius: 4),
          paint,
        );
        canvas.drawPath(
          _parseSvg('M4 21v-1a7 7 0 0 1 7-7h2a7 7 0 0 1 7 7v1'),
          paint,
        );
      case BrandIconName.search:
        canvas.drawOval(
          Rect.fromCircle(center: const Offset(11, 11), radius: 7),
          paint,
        );
        canvas.drawPath(_parseSvg('M21 21l-4.3-4.3'), paint);
      case BrandIconName.menu:
        canvas.drawPath(_parseSvg('M3 6h18M3 12h18M3 18h18'), paint);
    }

    canvas.restore();
  }

  @override
  bool shouldRepaint(covariant _BrandIconPainter old) =>
      old.icon != icon || old.color != color || old.strokeWidth != strokeWidth;
}

/// Minimal SVG path parser covering the subset of commands these icons use:
/// M/m, L/l, H/h, V/v, A/a (arc), Z/z.
Path _parseSvg(String data) {
  final path = Path();
  final len = data.length;
  int i = 0;

  bool isDigit(int cu) => cu >= 48 && cu <= 57;
  void skipSep() {
    while (i < len) {
      final cu = data.codeUnitAt(i);
      if (cu == 32 || cu == 44 || cu == 9 || cu == 10 || cu == 13) {
        i++;
      } else {
        break;
      }
    }
  }

  double number() {
    skipSep();
    var sign = 1.0;
    if (i < len) {
      final cu = data.codeUnitAt(i);
      if (cu == 45 || cu == 43) {
        if (cu == 45) sign = -1.0;
        i++;
      }
    }
    var v = 0.0;
    while (i < len && isDigit(data.codeUnitAt(i))) {
      v = v * 10 + (data.codeUnitAt(i) - 48);
      i++;
    }
    if (i < len && data.codeUnitAt(i) == 46) {
      i++;
      var f = 0.1;
      while (i < len && isDigit(data.codeUnitAt(i))) {
        v += f * (data.codeUnitAt(i) - 48);
        f /= 10;
        i++;
      }
    }
    if (i < len && (data.codeUnitAt(i) == 101 || data.codeUnitAt(i) == 69)) {
      i++;
      var esign = 1.0;
      if (i < len) {
        final eu = data.codeUnitAt(i);
        if (eu == 45 || eu == 43) {
          if (eu == 45) esign = -1.0;
          i++;
        }
      }
      var e = 0;
      while (i < len && isDigit(data.codeUnitAt(i))) {
        e = e * 10 + (data.codeUnitAt(i) - 48);
        i++;
      }
      v *= math.pow(10, esign * e).toDouble();
    }
    return v * sign;
  }

  bool nextIsDigit() {
    skipSep();
    if (i >= len) return false;
    final cu = data.codeUnitAt(i);
    return isDigit(cu) || cu == 45 || cu == 43 || cu == 46;
  }

  double cx = 0, cy = 0; // current point
  double sx = 0, sy = 0; // start of current subpath

  while (i < len) {
    skipSep();
    if (i >= len) break;
    final cu = data.codeUnitAt(i);
    i++;
    // Store the *uppercase* command code so the switch is simple.
    final cmd = cu < 97 ? cu : (cu - 32);
    final rel = cu >= 97; // lowercase → relative
    final bool close = (cu == 122) || (cu == 90);

    switch (cmd) {
      case 77: // M / m
        {
          var x = number(), y = number();
          if (rel) {
            x += cx;
            y += cy;
          }
          path.moveTo(x, y);
          sx = x;
          sy = y;
          cx = x;
          cy = y;
          while (nextIsDigit()) {
            var nx = number(), ny = number();
            if (rel) {
              nx += cx;
              ny += cy;
            }
            path.lineTo(nx, ny);
            cx = nx;
            cy = ny;
          }
        }
      case 76: // L / l
        {
          while (true) {
            var x = number(), y = number();
            if (rel) {
              x += cx;
              y += cy;
            }
            path.lineTo(x, y);
            cx = x;
            cy = y;
            if (!nextIsDigit()) break;
          }
        }
      case 72: // H / h
        {
          var x = number();
          if (rel) x += cx;
          path.lineTo(x, cy);
          cx = x;
        }
      case 86: // V / v
        {
          var y = number();
          if (rel) y += cy;
          path.lineTo(cx, y);
          cy = y;
        }
      case 65: // A / a
        {
          final rx = number().abs();
          final ry = number().abs();
          final rotation = number();
          final largeArc = number() != 0;
          final sweep = number() != 0;
          var x = number(), y = number();
          if (rel) {
            x += cx;
            y += cy;
          }
          path.arcToPoint(
            Offset(x, y),
            radius: Radius.elliptical(rx, ry),
            rotation: rotation,
            largeArc: largeArc,
            clockwise: sweep,
          );
          cx = x;
          cy = y;
        }
      case 90: // Z / z
        if (close) {
          path.close();
          cx = sx;
          cy = sy;
        }
    }
  }

  return path;
}
