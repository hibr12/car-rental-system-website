import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';

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
              'To rent a vehicle on DriveEase, you must meet the following criteria to ensure safety and compliance.',
              style: AppTypography.textTheme.bodyLarge,
            ),
            const SizedBox(height: AppSpacing.xxl),
            _buildRequirement(
              icon: LucideIcons.userCheck,
              title: 'Age Requirement',
              description:
                  'You must be at least 21 years old to rent standard vehicles. For luxury and performance vehicles, the minimum age is 25.',
            ),
            const SizedBox(height: AppSpacing.xl),
            _buildRequirement(
              icon: LucideIcons.fileBadge,
              title: 'Valid Driver\'s License',
              description:
                  'You must possess a valid driver\'s license that is not suspended, confiscated, or expired. Temporary licenses are not accepted.',
            ),
            const SizedBox(height: AppSpacing.xl),
            _buildRequirement(
              icon: LucideIcons.creditCard,
              title: 'Payment Method',
              description:
                  'A major credit card in your name must be provided for the reservation and security deposit. Debit cards may have additional restrictions.',
            ),
            const SizedBox(height: AppSpacing.xl),
            _buildRequirement(
              icon: LucideIcons.shieldCheck,
              title: 'Clean Driving Record',
              description:
                  'You must not have any major moving violations, DUIs, or reckless driving incidents within the past 3 years.',
            ),
            const SizedBox(height: AppSpacing.xxxl),
            Container(
              padding: const EdgeInsets.all(AppSpacing.lg),
              decoration: BoxDecoration(
                color: AppColors.primaryLight,
                borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Icon(LucideIcons.info, color: AppColors.primary),
                  const SizedBox(width: AppSpacing.md),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Identity Verification',
                            style: AppTypography.textTheme.titleMedium),
                        const SizedBox(height: AppSpacing.xs),
                        Text(
                          'Before your first trip, you may be asked to upload a photo of your license and a selfie for identity verification.',
                          style: AppTypography.textTheme.bodyMedium,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildRequirement(
      {required IconData icon,
      required String title,
      required String description}) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          padding: const EdgeInsets.all(AppSpacing.md),
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
            border: Border.all(color: AppColors.border),
          ),
          child: Icon(icon, color: AppColors.primary, size: 24),
        ),
        const SizedBox(width: AppSpacing.md),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: AppTypography.textTheme.titleLarge),
              const SizedBox(height: AppSpacing.xs),
              Text(description, style: AppTypography.textTheme.bodyLarge),
            ],
          ),
        ),
      ],
    );
  }
}
