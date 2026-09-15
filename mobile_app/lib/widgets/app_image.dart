import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';

import '../config/app_config.dart';
import '../theme/app_colors.dart';

/// A network image with a warm placeholder + soft error fallback so the flat
/// jewellery aesthetic holds even when remote media is slow or missing.
class AppImage extends StatelessWidget {
  const AppImage({
    super.key,
    this.url,
    this.fit = BoxFit.cover,
    this.borderRadius,
    this.placeholderColor = AppColors.warmBeige,
    this.width,
    this.height,
  });

  final String? url;
  final BoxFit fit;
  final BorderRadius? borderRadius;
  final Color placeholderColor;
  final double? width;
  final double? height;

  @override
  Widget build(BuildContext context) {
    final urlValue = AppConfig.resolveMediaUrl(url);

    Widget child = urlValue != null
        ? CachedNetworkImage(
            imageUrl: urlValue,
            fit: fit,
            width: width,
            height: height,
            placeholder: (_, __) => _Placeholder(color: placeholderColor),
            errorWidget: (_, __, ___) => _Placeholder(
              icon: Icons.image_outlined,
              color: placeholderColor,
            ),
          )
        : _Placeholder(icon: Icons.image_outlined, color: placeholderColor);

    if (borderRadius != null) {
      child = ClipRRect(borderRadius: borderRadius!, child: child);
    }
    return child;
  }
}

class _Placeholder extends StatelessWidget {
  const _Placeholder({this.color = AppColors.warmBeige, this.icon});

  final Color color;
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    return Container(
      color: color,
      alignment: Alignment.center,
      child: icon != null
          ? Icon(icon, color: AppColors.lineStrong, size: 26)
          : const SizedBox.shrink(),
    );
  }
}
