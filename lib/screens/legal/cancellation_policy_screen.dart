import 'package:flutter/material.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';

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
            Text('Free Cancellation',
                style: AppTypography.textTheme.displaySmall),
            const SizedBox(height: AppSpacing.md),
            Text(
              'We offer flexible cancellation options because we know plans change.',
              style: AppTypography.textTheme.bodyLarge,
            ),
            const SizedBox(height: AppSpacing.xl),
            _buildTimelineItem(
              title: 'More than 24 hours before trip',
              description: 'Cancel for a full refund. No fees or penalties.',
              color: AppColors.success,
            ),
            _buildTimelineItem(
              title: 'Less than 24 hours before trip',
              description:
                  'Cancel for a partial refund. A late cancellation fee of 50% of the first day\'s rental cost will apply.',
              color: AppColors.warning,
            ),
            _buildTimelineItem(
              title: 'After trip starts (No-show)',
              description:
                  'If you do not show up for your reservation and do not cancel, you will be charged the full amount for the first day and 50% for subsequent days.',
              color: AppColors.error,
              isLast: true,
            ),
            const SizedBox(height: AppSpacing.xxxl),
            Text('Host Cancellations',
                style: AppTypography.textTheme.headlineMedium),
            const SizedBox(height: AppSpacing.md),
            Text(
              'If a host cancels your trip, you will immediately receive a full refund, and our support team will help you find a replacement vehicle.',
              style: AppTypography.textTheme.bodyLarge,
            ),
            const SizedBox(height: AppSpacing.xxl),
            Text('Early Returns',
                style: AppTypography.textTheme.headlineMedium),
            const SizedBox(height: AppSpacing.md),
            Text(
              'If you return the vehicle early, you will be refunded for the unused days, minus a 20% early return fee on those unused days.',
              style: AppTypography.textTheme.bodyLarge,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTimelineItem(
      {required String title,
      required String description,
      required Color color,
      bool isLast = false}) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Column(
          children: [
            Container(
              width: 16,
              height: 16,
              decoration: BoxDecoration(
                color: color,
                shape: BoxShape.circle,
              ),
            ),
            if (!isLast)
              Container(
                width: 2,
                height: 80,
                color: AppColors.border,
                margin: const EdgeInsets.symmetric(vertical: 4),
              ),
          ],
        ),
        const SizedBox(width: AppSpacing.md),
        Expanded(
          child: Padding(
            padding: EdgeInsets.only(bottom: isLast ? 0 : AppSpacing.xl),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title,
                    style: AppTypography.textTheme.titleLarge
                        ?.copyWith(color: color)),
                const SizedBox(height: AppSpacing.sm),
                Text(description, style: AppTypography.textTheme.bodyLarge),
              ],
            ),
          ),
        ),
      ],
    );
  }
}
