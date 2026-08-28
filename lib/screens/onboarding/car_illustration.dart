import 'package:flutter/material.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';

/// Flat vector-style vehicle artwork drawn with [CustomPaint].
///
/// The app ships no image assets; painting the car keeps the onboarding
/// crisp on every screen density while staying inside the Apex Rentals
/// palette (one accent variant per page).
class CarIllustration extends StatelessWidget {
  final Color accent;
  final Widget? badge;

  const CarIllustration({
    super.key,
    required this.accent,
    this.badge,
  });

  @override
  Widget build(BuildContext context) {
    return AspectRatio(
      aspectRatio: 5 / 3,
      child: Stack(
        alignment: Alignment.center,
        children: [
          Container(
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(24),
              gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [
                  accent.withOpacity(0.08),
                  accent.withOpacity(0.03),
                ],
              ),
              border: Border.all(color: accent.withOpacity(0.12)),
            ),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(24),
              child: CustomPaint(
                size: Size.infinite,
                painter: _CarScenePainter(accent: accent),
              ),
            ),
          ),
          if (badge != null)
            Positioned(bottom: 12, child: badge!),
        ],
      ),
    );
  }
}

/// Small trust pill shown over the illustration (e.g. "Secure Chapa
/// payment"). All claims reflect real product features.
class IllustrationBadge extends StatelessWidget {
  final IconData icon;
  final String label;

  const IllustrationBadge({
    super.key,
    required this.icon,
    required this.label,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppSpacing.radiusPill),
        border: Border.all(color: AppColors.border),
        boxShadow: [
          BoxShadow(
            color: AppColors.textPrimary.withOpacity(0.08),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: AppColors.primary),
          const SizedBox(width: 6),
          Text(
            label,
            style: Theme.of(context).textTheme.labelMedium?.copyWith(
                  color: AppColors.textSecondary,
                  fontWeight: FontWeight.w600,
                ),
          ),
        ],
      ),
    );
  }
}

class _CarScenePainter extends CustomPainter {
  final Color accent;

  _CarScenePainter({required this.accent});

  // Virtual canvas: 100 x 60 units, mapped proportionally.
  double _x(double u, double w) => u * w / 100;
  double _y(double v, double h) => v * h / 60;

  @override
  void paint(Canvas canvas, Size size) {
    final w = size.width;
    final h = size.height;

    // Decorative background shapes.
    final decorPaint = Paint()..color = accent.withOpacity(0.07);
    canvas.drawCircle(Offset(_x(12, w), _y(12, h)), _x(16, w), decorPaint);
    canvas.drawCircle(Offset(_x(90, w), _y(52, h)), _x(12, w), decorPaint);

    // Ground shadow.
    canvas.drawOval(
      Rect.fromCenter(
        center: Offset(_x(50, w), _y(53, h)),
        width: _x(80, w),
        height: _y(5, h),
      ),
      Paint()..color = AppColors.textPrimary.withOpacity(0.06),
    );

    final bodyPaint = Paint()..color = accent;
    final glassPaint = Paint()
      ..color = AppColors.surface.withOpacity(0.92);
    final trimPaint = Paint()
      ..color = AppColors.textPrimary.withOpacity(0.14);
    final tyrePaint = Paint()..color = AppColors.textPrimary;
    final hubPaint = Paint()..color = AppColors.surface;

    // Cabin.
    final cabin = Path()
      ..moveTo(_x(24, w), _y(30, h))
      ..quadraticBezierTo(_x(29, w), _y(13, h), _x(43, w), _y(12, h))
      ..lineTo(_x(58, w), _y(12, h))
      ..quadraticBezierTo(_x(70, w), _y(13, h), _x(76, w), _y(30, h))
      ..close();
    canvas.drawPath(cabin, bodyPaint);

    // Body tub.
    canvas.drawRRect(
      RRect.fromRectAndRadius(
        Rect.fromLTRB(_x(6, w), _y(28, h), _x(94, w), _y(45, h)),
        Radius.circular(_y(8, h)),
      ),
      bodyPaint,
    );

    // Lower trim for depth.
    canvas.drawRRect(
      RRect.fromRectAndRadius(
        Rect.fromLTRB(_x(10, w), _y(40, h), _x(90, w), _y(45, h)),
        Radius.circular(_y(4, h)),
      ),
      trimPaint,
    );

    // Windows.
    final rearWindow = Path()
      ..moveTo(_x(28.5, w), _y(28, h))
      ..quadraticBezierTo(_x(32, w), _y(17, h), _x(42, w), _y(16, h))
      ..lineTo(_x(48, w), _y(16, h))
      ..lineTo(_x(48, w), _y(28, h))
      ..close();
    canvas.drawPath(rearWindow, glassPaint);

    final frontWindow = Path()
      ..moveTo(_x(52, w), _y(16, h))
      ..lineTo(_x(59, w), _y(16, h))
      ..quadraticBezierTo(_x(68, w), _y(17, h), _x(72, w), _y(28, h))
      ..lineTo(_x(52, w), _y(28, h))
      ..close();
    canvas.drawPath(frontWindow, glassPaint);

    // Door seam + handle.
    canvas.drawLine(
      Offset(_x(50, w), _y(17.5, h)),
      Offset(_x(50, w), _y(39, h)),
      Paint()
        ..color = AppColors.textPrimary.withOpacity(0.15)
        ..strokeWidth = _x(1, w),
    );
    canvas.drawRRect(
      RRect.fromRectAndRadius(
        Rect.fromLTRB(_x(54, w), _y(32, h), _x(61, w), _y(34, h)),
        const Radius.circular(2),
      ),
      Paint()..color = AppColors.textPrimary.withOpacity(0.28),
    );

    // Lights.
    canvas.drawCircle(
        Offset(_x(88.5, w), _y(33, h)), _x(2.4, w), hubPaint);
    canvas.drawCircle(
        Offset(_x(10, w), _y(33, h)), _x(2, w),
        Paint()..color = AppColors.surface.withOpacity(0.85));

    // Wheels.
    final wheelCenters = [
      Offset(_x(28, w), _y(45, h)),
      Offset(_x(74, w), _y(45, h)),
    ];
    for (final c in wheelCenters) {
      canvas.drawCircle(c, _x(8.4, w), tyrePaint);
      canvas.drawCircle(c, _x(3.8, w), hubPaint);
      canvas.drawCircle(
        c,
        _x(1.4, w),
        Paint()..color = AppColors.border,
      );
    }
  }

  @override
  bool shouldRepaint(covariant _CarScenePainter oldDelegate) =>
      oldDelegate.accent != accent;
}
