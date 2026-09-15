import 'package:flutter/material.dart';

import '../theme/app_colors.dart';
import '../theme/app_typography.dart';

/// A minus/plus quantity control used on cart and product-detail pages.
class QuantityStepper extends StatelessWidget {
  const QuantityStepper({
    super.key,
    required this.quantity,
    this.onChanged,
    this.min = 1,
    this.max = 10,
    this.enabled = true,
    this.size = 34,
  });

  final int quantity;
  final ValueChanged<int>? onChanged;
  final int min;
  final int max;
  final bool enabled;
  final double size;

  @override
  Widget build(BuildContext context) {
    final canDecrement = enabled && quantity > min;
    final canIncrement = enabled && quantity < max;

    return Container(
      decoration: BoxDecoration(
        border: Border.all(color: AppColors.lineStrong),
        borderRadius: BorderRadius.circular(2),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          _Button(
            icon: Icons.remove_rounded,
            size: size,
            onTap: canDecrement ? () => onChanged?.call(quantity - 1) : null,
          ),
          SizedBox(
            width: size,
            child: Text(
              '$quantity',
              textAlign: TextAlign.center,
              style: AppTypography.bodyMedium(weight: FontWeight.w700),
            ),
          ),
          _Button(
            icon: Icons.add_rounded,
            size: size,
            onTap: canIncrement ? () => onChanged?.call(quantity + 1) : null,
          ),
        ],
      ),
    );
  }
}

class _Button extends StatelessWidget {
  const _Button({required this.icon, required this.size, this.onTap});

  final IconData icon;
  final double size;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      child: SizedBox(
        width: size,
        height: size,
        child: Icon(
          icon,
          size: size * 0.55,
          color: onTap == null ? AppColors.lineStrong : AppColors.ink,
        ),
      ),
    );
  }
}
