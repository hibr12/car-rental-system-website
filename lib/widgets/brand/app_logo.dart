import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';

/// Apex Rentals brand assets rendered in code so splash/auth screens stay
/// crisp at any density without shipping image assets.
///
/// Matches the web frontend brand: a rounded-square primary-blue tile with
/// a white car glyph (Navbar), a two-tone "Apex Rentals" wordmark and the
/// uppercase "Drive Premium" tagline.
class AppLogo {
  AppLogo._();

  /// The square brand mark used on the web navbar.
  static Widget mark({
    double size = 48,
    Color background = AppColors.primary,
    Color foreground = AppColors.surface,
    double borderRadius = AppSpacing.radiusMd,
  }) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(borderRadius),
      ),
      child: Icon(
        LucideIcons.car,
        size: size * 0.55,
        color: foreground,
      ),
    );
  }

  /// Horizontal lockup: mark + two-tone wordmark (+ optional tagline).
  static Widget lockup({
    double markSize = 44,
    bool showTagline = true,
    Color textColor = AppColors.textPrimary,
    Color taglineColor = AppColors.textTertiary,
    Color markBackground = AppColors.primary,
    Color markForeground = AppColors.surface,
    MainAxisAlignment alignment = MainAxisAlignment.start,
  }) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      mainAxisAlignment: alignment,
      children: [
        mark(
          size: markSize,
          background: markBackground,
          foreground: markForeground,
        ),
        const SizedBox(width: AppSpacing.md),
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Text.rich(
              TextSpan(
                text: 'Apex',
                style: AppTypography.textTheme.headlineMedium?.copyWith(
                  color: textColor,
                  fontWeight: FontWeight.w800,
                  letterSpacing: -0.5,
                ),
                children: [
                  TextSpan(
                    text: 'Rentals',
                    style: AppTypography.textTheme.headlineMedium?.copyWith(
                      color: AppColors.primary,
                      fontWeight: FontWeight.w800,
                      letterSpacing: -0.5,
                    ),
                  ),
                ],
              ),
            ),
            if (showTagline) ...[
              const SizedBox(height: 2),
              Text(
                'DRIVE PREMIUM',
                style: AppTypography.textTheme.labelSmall?.copyWith(
                  color: taglineColor,
                  fontWeight: FontWeight.w700,
                  fontSize: 10,
                  letterSpacing: 2.4,
                ),
              ),
            ],
          ],
        ),
      ],
    );
  }
}
