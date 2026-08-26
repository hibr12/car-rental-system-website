import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';

/// Informational insurance summary.
///
/// The backend has no insurance product/tier system — the app must not
/// invent purchasable coverage plans. This page explains the baseline
/// expectations and points customers to support for specifics.
class InsurancePolicyScreen extends StatelessWidget {
  const InsurancePolicyScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('Insurance & Liability')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(AppSpacing.pagePadding),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Insurance Basics',
                style: AppTypography.textTheme.displaySmall),
            const SizedBox(height: AppSpacing.md),
            Text(
              'Every rental vehicle is insured in line with Ethiopian legal '
              'requirements. The exact coverage that applies to your rental '
              'is confirmed at pickup and recorded on your rental agreement.',
              style: AppTypography.textTheme.bodyLarge,
            ),
            const SizedBox(height: AppSpacing.xxl),
            _buildSection(
              icon: LucideIcons.shieldCheck,
              title: 'Before you drive',
              points: [
                'Inspect the vehicle with branch staff and note any existing damage before leaving the branch.',
                'Confirm the fuel level and mileage recorded at checkout.',
                'Make sure your driver license is verified in this app.',
              ],
            ),
            const SizedBox(height: AppSpacing.lg),
            _buildSection(
              icon: LucideIcons.alertTriangle,
              title: 'Customer responsibility',
              points: [
                'Drive within Ethiopian traffic law — fines and penalties for violations are the renter\'s responsibility.',
                'Only drivers approved on the booking may operate the vehicle.',
                'Report any accident or damage to the branch immediately.',
              ],
            ),
            const SizedBox(height: AppSpacing.lg),
            _buildSection(
              icon: LucideIcons.fileX,
              title: 'Not covered',
              points: [
                'Damage from prohibited use such as off-road driving or racing.',
                'Interior damage beyond normal wear (spills, tears, burns).',
                'Loss of personal belongings.',
              ],
            ),
            const SizedBox(height: AppSpacing.xxxl),
            Text(
              'Questions about coverage limits, deductibles, or additional '
              'protection? Contact our team and we will walk you through '
              'the details before you book.',
              style: AppTypography.textTheme.bodyMedium
                  ?.copyWith(color: AppColors.textSecondary),
            ),
            const SizedBox(height: AppSpacing.lg),
          ],
        ),
      ),
    );
  }

  Widget _buildSection({
    required IconData icon,
    required String title,
    required List<String> points,
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
              Icon(icon, size: 20, color: AppColors.primary),
              const SizedBox(width: AppSpacing.sm),
              Expanded(
                  child: Text(title,
                      style: AppTypography.textTheme.titleLarge)),
            ],
          ),
          const SizedBox(height: AppSpacing.md),
          ...points.map((p) => Padding(
                padding: const EdgeInsets.only(bottom: 8.0),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Icon(Icons.check_circle,
                        size: 16, color: AppColors.success),
                    const SizedBox(width: AppSpacing.sm),
                    Expanded(
                        child:
                            Text(p, style: AppTypography.textTheme.bodyMedium)),
                  ],
                ),
              )),
        ],
      ),
    );
  }
}
