import 'package:flutter/material.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';

/// Terms of Service screen.
///
/// Master platform terms governing account usage, booking conditions,
/// payment obligations, and user conduct.
class TermsOfServiceScreen extends StatelessWidget {
  const TermsOfServiceScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('Terms of Service')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(AppSpacing.pagePadding),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Terms of Service',
                style: AppTypography.textTheme.headlineMedium),
            const SizedBox(height: AppSpacing.xs),
            Text(
              'Effective: September 2026',
              style: AppTypography.textTheme.bodySmall
                  ?.copyWith(color: AppColors.textTertiary),
            ),
            const SizedBox(height: AppSpacing.lg),

            _buildSection(
              title: '1. Acceptance of Terms',
              content:
                  'By creating an account or using the Apex Rentals application, you agree to be bound by these Terms of Service and all related rental guidelines. If you do not agree, please do not use the application.',
            ),
            const SizedBox(height: AppSpacing.lg),

            _buildSection(
              title: '2. User Eligibility & Account Responsibilities',
              content:
                  '• You must be at least 21 years old and hold a valid, government-issued driver\'s license.\n'
                  '• You must maintain true, accurate, and current account information.\n'
                  '• You are responsible for safeguarding your login credentials and preventing unauthorized access to your account.\n'
                  '• Renters may not sub-lease or transfer custody of any rented vehicle to unapproved third parties.',
            ),
            const SizedBox(height: AppSpacing.lg),

            _buildSection(
              title: '3. Reservations, Rates & Payments',
              content:
                  '• Vehicle availability is guaranteed only once a reservation is confirmed and verified.\n'
                  '• All rental charges, daily rates, taxes, and security deposits are displayed clearly before booking confirmation.\n'
                  '• Payments are processed securely via Chapa online gateway or in cash directly at the branch.\n'
                  '• Fuel, tolls, traffic citations, and parking penalties incurred during the rental are the sole responsibility of the renter.',
            ),
            const SizedBox(height: AppSpacing.lg),

            _buildSection(
              title: '4. Vehicle Pickup, Inspection & Returns',
              content:
                  '• A physical vehicle inspection is performed and documented both at pickup and return.\n'
                  '• Vehicles must be returned to the agreed branch location at the scheduled time and date.\n'
                  '• Late returns without prior extension approval may incur overtime surcharges.',
            ),
            const SizedBox(height: AppSpacing.lg),

            _buildSection(
              title: '5. Account Deletion & Termination',
              content:
                  '• You may request permanent deletion of your account at any time via Settings.\n'
                  '• Deletion is processed immediately provided there are no pending or active rentals.\n'
                  '• Apex Rentals reserves the right to suspend or terminate accounts in violation of safety rules or payment obligations.',
            ),
            const SizedBox(height: AppSpacing.lg),

            _buildSection(
              title: '6. Limitation of Liability',
              content:
                  'Apex Rentals shall not be liable for indirect, incidental, or consequential damages resulting from platform downtime, vehicle breakdowns, or force majeure events, subject to mandatory local laws.',
            ),
            const SizedBox(height: AppSpacing.xxl),
          ],
        ),
      ),
    );
  }

  Widget _buildSection({required String title, required String content}) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(AppSpacing.lg),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: AppTypography.textTheme.titleMedium
                ?.copyWith(fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: AppSpacing.sm),
          Text(
            content,
            style: AppTypography.textTheme.bodyMedium?.copyWith(
              color: AppColors.textSecondary,
              height: 1.5,
            ),
          ),
        ],
      ),
    );
  }
}
