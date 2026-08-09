import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../../../core/colors/app_colors.dart';
import '../../../../core/spacing/app_spacing.dart';
import '../../../../core/typography/app_typography.dart';
import '../../../../models/booking_model.dart';

class QRCodeScreen extends StatelessWidget {
  final Booking booking;

  const QRCodeScreen({super.key, required this.booking});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('Pickup QR Code')),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.pagePadding),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(
                'Show this code at the branch',
                style: AppTypography.textTheme.titleLarge,
              ),
              const SizedBox(height: AppSpacing.xxl),
              Container(
                padding: const EdgeInsets.all(AppSpacing.xl),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
                  border: Border.all(color: AppColors.border),
                ),
                child: const Icon(LucideIcons.qrCode,
                    size: 200, color: AppColors.textPrimary),
              ),
              const SizedBox(height: AppSpacing.xl),
              Text(
                booking.bookingReference,
                style: AppTypography.textTheme.headlineMedium
                    ?.copyWith(letterSpacing: 4),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
