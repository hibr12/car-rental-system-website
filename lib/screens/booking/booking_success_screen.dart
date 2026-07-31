import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../core/routes/app_routes.dart';
import '../../models/booking_model.dart';
import '../../models/vehicle_model.dart';
import '../../widgets/buttons/app_buttons.dart';

/// Shown after a booking is successfully created.
///
/// Preferred: pass a real [Booking] (carries `bookingReference`).
/// Fallback:  pass a [Vehicle] (legacy callers / route guard).
class BookingSuccessScreen extends StatelessWidget {
  final Booking? booking;
  final Vehicle? vehicle;

  /// Preferred constructor — real booking with reference number.
  const BookingSuccessScreen({super.key, this.booking, this.vehicle});

  /// Convenience: construct with only a Booking.
  const BookingSuccessScreen.fromBooking(
      {super.key, required Booking this.booking})
      : vehicle = null;

  String get _vehicleName =>
      booking?.vehicle.fullName ?? vehicle?.fullName ?? 'your vehicle';

  String get _reference => booking?.bookingReference ?? '';

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(AppSpacing.pagePadding),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Container(
                padding: const EdgeInsets.all(AppSpacing.xxl),
                decoration: BoxDecoration(
                  color: AppColors.success.withOpacity(0.1),
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  LucideIcons.checkCircle,
                  size: 80,
                  color: AppColors.success,
                ),
              ),
              const SizedBox(height: AppSpacing.xxl),
              Text(
                'Booking Confirmed!',
                style: AppTypography.textTheme.displayMedium,
              ),
              const SizedBox(height: AppSpacing.md),
              Text(
                'You are all set for your trip in the $_vehicleName. '
                'We have sent the itinerary to your email.',
                style: AppTypography.textTheme.bodyLarge,
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: AppSpacing.xxl),

              // Reference card — only show if we have a real reference.
              if (_reference.isNotEmpty)
                Container(
                  padding: const EdgeInsets.all(AppSpacing.lg),
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
                    border: Border.all(color: AppColors.border),
                  ),
                  child: Column(
                    children: [
                      Text('Booking Reference',
                          style: AppTypography.textTheme.bodyMedium),
                      const SizedBox(height: AppSpacing.xs),
                      Text(
                        _reference,
                        style: AppTypography.textTheme.headlineMedium
                            ?.copyWith(color: AppColors.primary),
                      ),
                    ],
                  ),
                ),

              // Payment status hint
              if (booking != null) ...[
                const SizedBox(height: AppSpacing.md),
                Container(
                  padding: const EdgeInsets.symmetric(
                      horizontal: AppSpacing.md, vertical: AppSpacing.sm),
                  decoration: BoxDecoration(
                    color: AppColors.warning.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(LucideIcons.info,
                          size: 16, color: AppColors.warning),
                      const SizedBox(width: AppSpacing.xs),
                      Flexible(
                        child: Text(
                          'Payment: ${booking!.paymentStatus.label}',
                          style: AppTypography.textTheme.labelMedium
                              ?.copyWith(color: AppColors.warning),
                        ),
                      ),
                    ],
                  ),
                ),
              ],

              const SizedBox(height: AppSpacing.xxxl),
              PrimaryButton(
                text: 'View My Bookings',
                onPressed: () {
                  context.go(AppRoutes.home);
                },
              ),
              const SizedBox(height: AppSpacing.md),
              SecondaryButton(
                text: 'Back to Home',
                onPressed: () => context.go(AppRoutes.home),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
