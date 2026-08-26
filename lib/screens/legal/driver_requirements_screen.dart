import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';

/// Driver requirements, aligned with the backend's actual verification
/// rules: a submitted driver's license (front + back) that our team
/// verifies before bookings are allowed.
class DriverRequirementsScreen extends StatelessWidget {
  const DriverRequirementsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('Driver Requirements')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(AppSpacing.pagePadding),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Who can drive?', style: AppTypography.textTheme.displaySmall),
            const SizedBox(height: AppSpacing.md),
            Text(
              'To rent and drive with Apex Rentals you need an account and a '
              'verified driver\'s license. Here is how it works.',
              style: AppTypography.textTheme.bodyLarge,
            ),
            const SizedBox(height: AppSpacing.xxl),
            _buildRequirement(
              icon: LucideIcons.fileBadge,
              title: 'Submit your license',
              description:
                  'Upload clear photos or scans of the front and back of your valid driver\'s license from Profile → Driver\'s License. Accepted formats: JPG, PNG, WEBP, or PDF up to 5 MB each.',
            ),
            const SizedBox(height: AppSpacing.xl),
            _buildRequirement(
              icon: LucideIcons.shieldCheck,
              title: 'Get it verified',
              description:
                  'Our team reviews your documents. You will receive a notification once your license is approved. Bookings require a verified license.',
            ),
            const SizedBox(height: AppSpacing.xl),
            _buildRequirement(
              icon: LucideIcons.fileBadge,
              title: 'Matching license category',
              description:
                  'Your license category must qualify for the vehicle you book — categories include automobile, motorcycle, minibus, commercial, and heavy vehicles.',
            ),
            const SizedBox(height: AppSpacing.xl),
            _buildRequirement(
              icon: LucideIcons.calendarCheck,
              title: 'Valid for the rental period',
              description:
                  'Your license must remain valid through your rental dates. An expired license blocks new bookings until you upload your renewed one.',
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildRequirement({
    required IconData icon,
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
              Container(
                padding: const EdgeInsets.all(AppSpacing.sm),
                decoration: BoxDecoration(
                  color: AppColors.primaryLight,
                  borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
                ),
                child: Icon(icon, size: 20, color: AppColors.primary),
              ),
              const SizedBox(width: AppSpacing.md),
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
