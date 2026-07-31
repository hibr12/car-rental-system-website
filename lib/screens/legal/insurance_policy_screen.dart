import 'package:flutter/material.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';

class InsurancePolicyScreen extends StatelessWidget {
  const InsurancePolicyScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('Insurance Policy')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(AppSpacing.pagePadding),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Coverage Plans', style: AppTypography.textTheme.displaySmall),
            const SizedBox(height: AppSpacing.md),
            Text(
              'We offer three tiers of protection for your peace of mind.',
              style: AppTypography.textTheme.bodyLarge,
            ),
            const SizedBox(height: AppSpacing.xl),
            _buildPlanCard(
              title: 'Minimum Protection',
              price: 'Included',
              features: [
                'State minimum liability coverage',
                '\$3,000 max out-of-pocket for vehicle damage',
                '24/7 customer support',
              ],
              isPopular: false,
            ),
            const SizedBox(height: AppSpacing.md),
            _buildPlanCard(
              title: 'Standard Protection',
              price: '\$15 / day',
              features: [
                '\$1M liability coverage',
                '\$500 max out-of-pocket for vehicle damage',
                '24/7 roadside assistance',
              ],
              isPopular: true,
            ),
            const SizedBox(height: AppSpacing.md),
            _buildPlanCard(
              title: 'Premium Protection',
              price: '\$25 / day',
              features: [
                '\$1M liability coverage',
                '\$0 out-of-pocket for vehicle damage',
                '24/7 roadside assistance',
                'Personal effects coverage',
              ],
              isPopular: false,
            ),
            const SizedBox(height: AppSpacing.xxxl),
            Text('What is not covered?',
                style: AppTypography.textTheme.headlineMedium),
            const SizedBox(height: AppSpacing.md),
            Text(
              '• Damage caused by prohibited use of the vehicle (e.g., off-roading, racing)\n'
              '• Damage to the interior of the vehicle (e.g., spills, tears, burns)\n'
              '• Wear and tear from normal use\n'
              '• Loss of personal property (unless Premium Protection is selected)\n'
              '• Damage caused by an unauthorized driver',
              style: AppTypography.textTheme.bodyLarge,
            ),
            const SizedBox(height: AppSpacing.xxl),
          ],
        ),
      ),
    );
  }

  Widget _buildPlanCard(
      {required String title,
      required String price,
      required List<String> features,
      required bool isPopular}) {
    return Container(
      padding: const EdgeInsets.all(AppSpacing.lg),
      decoration: BoxDecoration(
        color: isPopular ? AppColors.primaryLight : AppColors.surface,
        borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
        border: Border.all(
            color: isPopular ? AppColors.primary : AppColors.border,
            width: isPopular ? 2 : 1),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (isPopular) ...[
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
              decoration: BoxDecoration(
                color: AppColors.primary,
                borderRadius: BorderRadius.circular(AppSpacing.radiusPill),
              ),
              child: Text('Most Popular',
                  style: AppTypography.textTheme.labelSmall
                      ?.copyWith(color: AppColors.surface)),
            ),
            const SizedBox(height: AppSpacing.md),
          ],
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(title, style: AppTypography.textTheme.titleLarge),
              Text(price,
                  style: AppTypography.textTheme.titleMedium
                      ?.copyWith(color: AppColors.primary)),
            ],
          ),
          const SizedBox(height: AppSpacing.md),
          ...features.map((f) => Padding(
                padding: const EdgeInsets.only(bottom: 8.0),
                child: Row(
                  children: [
                    const Icon(Icons.check_circle,
                        size: 16, color: AppColors.success),
                    const SizedBox(width: AppSpacing.sm),
                    Expanded(
                        child:
                            Text(f, style: AppTypography.textTheme.bodyMedium)),
                  ],
                ),
              )),
        ],
      ),
    );
  }
}
