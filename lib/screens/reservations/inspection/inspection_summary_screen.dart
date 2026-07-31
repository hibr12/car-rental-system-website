import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:go_router/go_router.dart';
import '../../../core/colors/app_colors.dart';
import '../../../core/spacing/app_spacing.dart';
import '../../../core/typography/app_typography.dart';
import '../../../widgets/buttons/app_buttons.dart';
import '../../../core/routes/app_routes.dart';

class InspectionSummaryScreen extends StatelessWidget {
  final bool isReturn;

  const InspectionSummaryScreen({super.key, required this.isReturn});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Inspection Complete'),
        automaticallyImplyLeading: false, // Prevent going back to flow
      ),
      body: Padding(
        padding: const EdgeInsets.all(AppSpacing.pagePadding),
        child: Column(
          children: [
            Expanded(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Container(
                    padding: const EdgeInsets.all(AppSpacing.xl),
                    decoration: BoxDecoration(
                      color: AppColors.success.withOpacity(0.1),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(LucideIcons.checkCircle,
                        size: 64, color: AppColors.success),
                  ),
                  const SizedBox(height: AppSpacing.xxl),
                  Text(
                    isReturn ? 'Return Complete!' : 'You\'re All Set!',
                    style: AppTypography.textTheme.displayMedium,
                  ),
                  const SizedBox(height: AppSpacing.md),
                  Text(
                    isReturn
                        ? 'The vehicle has been successfully returned. We will process any final charges or refunds shortly.'
                        : 'The inspection is complete. Here are the keys, enjoy your trip!',
                    style: AppTypography.textTheme.bodyLarge,
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: AppSpacing.xxxl),
                  _buildSummaryCard(),
                ],
              ),
            ),
            PrimaryButton(
              text: isReturn ? 'Back to Home' : 'Start Trip',
              onPressed: () {
                context.go(AppRoutes.home);
              },
              isFullWidth: true,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSummaryCard() {
    return Container(
      padding: const EdgeInsets.all(AppSpacing.lg),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('Reported Damage', style: AppTypography.textTheme.bodyLarge),
              Text('None', style: AppTypography.textTheme.titleMedium),
            ],
          ),
          const Divider(height: AppSpacing.xl),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('Fuel Level', style: AppTypography.textTheme.bodyLarge),
              Text('100%', style: AppTypography.textTheme.titleMedium),
            ],
          ),
          const Divider(height: AppSpacing.xl),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('Mileage', style: AppTypography.textTheme.bodyLarge),
              Text('45,200 mi', style: AppTypography.textTheme.titleMedium),
            ],
          ),
        ],
      ),
    );
  }
}
