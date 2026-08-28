import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';

/// Cancellation policy, aligned with the backend's actual rules:
/// customers may cancel while a booking is awaiting payment or approval;
/// paid bookings are refunded through a staff-reviewed process.
class CancellationPolicyScreen extends StatelessWidget {
  const CancellationPolicyScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('Cancellation Policy')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(AppSpacing.pagePadding),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Cancelling a Booking',
                style: AppTypography.textTheme.displaySmall),
            const SizedBox(height: AppSpacing.md),
            Text(
              'We know plans change. Here is exactly how cancellation works '
              'so there are no surprises.',
              style: AppTypography.textTheme.bodyLarge,
            ),
            const SizedBox(height: AppSpacing.xxl),
            _buildItem(
              icon: LucideIcons.calendarX,
              color: AppColors.success,
              title: 'Before payment & approval',
              description:
                  'Bookings awaiting payment or approval can be cancelled directly from the app at no cost.',
            ),
            const SizedBox(height: AppSpacing.lg),
            _buildItem(
              icon: LucideIcons.rotateCcw,
              color: AppColors.warning,
              title: 'Already paid?',
              description:
                  'If your booking was already paid (online or in cash) and you cancel it, our team reviews and processes your refund. Contact support with your booking reference to speed things up.',
            ),
            const SizedBox(height: AppSpacing.lg),
            _buildItem(
              icon: LucideIcons.xCircle,
              color: AppColors.error,
              title: 'After approval or pickup',
              description:
                  'Once a booking is approved for pickup or the rental has started, cancellation is handled case by case by branch staff — please call or visit the branch.',
            ),
            const SizedBox(height: AppSpacing.xxxl),
            Text('Company cancellations',
                style: AppTypography.textTheme.headlineMedium),
            const SizedBox(height: AppSpacing.md),
            Text(
              'In the rare event we need to cancel your booking — for example if the vehicle becomes unavailable — you will be notified promptly and any payment already made will be refunded.',
              style: AppTypography.textTheme.bodyMedium
                  ?.copyWith(color: AppColors.textSecondary),
            ),
            const SizedBox(height: AppSpacing.xxl),
          ],
        ),
      ),
    );
  }

  Widget _buildItem({
    required IconData icon,
    required Color color,
    required String title,
    required String description,
  }) {
    return Container(
      padding: const EdgeInsets.all(AppSpacing.lg),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 20, color: color),
              const SizedBox(width: AppSpacing.sm),
              Expanded(
                  child: Text(title,
                      style: AppTypography.textTheme.titleLarge)),
            ],
          ),
          const SizedBox(height: AppSpacing.sm),
          Text(description, style: AppTypography.textTheme.bodyMedium),
        ],
      ),
    );
  }
}
