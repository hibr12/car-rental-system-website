import 'package:flutter/material.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';

/// Read-only rental agreement reference.
///
/// The actual rental agreement is signed at the branch during pickup —
/// this page is for customers to review the standard terms beforehand.
class RentalAgreementScreen extends StatelessWidget {
  const RentalAgreementScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('Rental Agreement')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(AppSpacing.pagePadding),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Standard Terms',
                style: AppTypography.textTheme.headlineMedium),
            const SizedBox(height: AppSpacing.md),
            Container(
              padding: const EdgeInsets.all(AppSpacing.lg),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
                border: Border.all(color: AppColors.border),
              ),
              child: const Text(
                '1. The Renter agrees to return the vehicle in the same condition as received.\n\n'
                '2. The Renter is responsible for all traffic fines, tolls, and parking tickets incurred during the rental period.\n\n'
                '3. In case of an accident, the Renter must immediately notify the rental company and local authorities.\n\n'
                '4. The vehicle must not be used for racing, towing, or any illegal activities.\n\n'
                '5. The Renter must hold a valid driver\'s license covering the rented vehicle category for the entire rental duration.\n\n'
                '6. The full rental price and any additional charges are confirmed by the branch before pickup; payment is completed via Chapa or in cash at the branch.',
              ),
            ),
            const SizedBox(height: AppSpacing.xxl),
            Container(
              padding: const EdgeInsets.all(AppSpacing.md),
              decoration: BoxDecoration(
                color: AppColors.primaryLight,
                borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
              ),
              child: Row(
                children: [
                  const Icon(Icons.info_outline,
                      size: 20, color: AppColors.primary),
                  const SizedBox(width: AppSpacing.sm),
                  Expanded(
                    child: Text(
                      'Your binding rental agreement is signed at the branch '
                      'when you collect the vehicle.',
                      style: AppTypography.textTheme.bodyMedium
                          ?.copyWith(color: AppColors.primary),
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
}
