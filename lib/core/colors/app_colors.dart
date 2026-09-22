import 'package:flutter/material.dart';

class AppColors {
  AppColors._();

  // Brand Colors
  static const Color primary = Color(0xFF2563EB); // Vibrant Blue
  static const Color primaryDark = Color(0xFF1D4ED8);
  static const Color primaryLight = Color(0xFFEFF6FF);

  static const Color secondary = Color(0xFF0EA5E9); // Sky Blue (matches web)

  // Background Colors
  static const Color background = Color(0xFFF8FAFC); // Slate 50
  static const Color surface = Color(0xFFFFFFFF);
  static const Color surfaceElevated = Color(0xFFF1F5F9); // Slate 100

  /// Alias matching the web token `--apex-background-secondary`.
  static const Color backgroundSecondary = Color(0xFFF8FAFC);

  // Text Colors
  static const Color textPrimary = Color(0xFF0F172A); // Slate 900
  static const Color textSecondary = Color(0xFF334155); // Slate 700 (matches web)
  static const Color textTertiary = Color(0xFF94A3B8); // Slate 400
  static const Color textInverse = AppColors.surface;

  // Status Colors
  static const Color success = Color(0xFF16A34A); // Green 600 (matches web)
  static const Color error = Color(0xFFDC2626); // Red 600 (matches web)
  static const Color warning = Color(0xFFF59E0B); // Amber 500
  static const Color info = Color(0xFF3B82F6); // Blue 500

  // Border Colors
  static const Color border = Color(0xFFE2E8F0); // Slate 200
  static const Color borderFocused = primary;

  // Divider
  static const Color divider = Color(0xFFE2E8F0); // Slate 200
}
