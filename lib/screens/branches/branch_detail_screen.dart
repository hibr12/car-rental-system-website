import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:go_router/go_router.dart';
import '../../core/colors/app_colors.dart';
import '../../core/routes/app_routes.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../models/branch_model.dart';
import '../../widgets/buttons/app_buttons.dart';

/// Branch details. Shows only data the backend actually provides
/// (address, city, phone, email, status, manager, fleet count).
class BranchDetailScreen extends StatelessWidget {
  final Branch branch;

  const BranchDetailScreen({super.key, required this.branch});

  Future<void> _openDirections() async {
    // No coordinates exist in the backend; a geo search on the branch's
    // address is the honest way to help customers navigate.
    final query = Uri.encodeComponent('${branch.locationLine}, Ethiopia');
    final uri = Uri.parse(
        'https://www.google.com/maps/search/?api=1&query=$query');
    try {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } catch (_) {
      // Nothing else we can do if no maps app/browser is available.
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('Branch Details')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(AppSpacing.pagePadding),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(AppSpacing.md),
                  decoration: BoxDecoration(
                    color: AppColors.primaryLight,
                    borderRadius:
                        BorderRadius.circular(AppSpacing.radiusLg),
                  ),
                  child: const Icon(LucideIcons.building,
                      size: 40, color: AppColors.primary),
                ),
                const SizedBox(width: AppSpacing.md),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(branch.name,
                          style: AppTypography.textTheme.headlineMedium),
                      const SizedBox(height: AppSpacing.xs),
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: branch.isActive
                              ? AppColors.success.withOpacity(0.1)
                              : AppColors.textTertiary.withOpacity(0.15),
                          borderRadius:
                              BorderRadius.circular(AppSpacing.radiusSm),
                        ),
                        child: Text(
                          branch.isActive ? 'Open' : 'Closed',
                          style: AppTypography.textTheme.labelSmall?.copyWith(
                            color: branch.isActive
                                ? AppColors.success
                                : AppColors.textTertiary,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: AppSpacing.xxl),
            _buildInfoRow(
                LucideIcons.mapPin, branch.locationLine),
            if (branch.phone.isNotEmpty)
              _buildInfoRow(LucideIcons.phone, branch.phone),
            if (branch.email.isNotEmpty)
              _buildInfoRow(LucideIcons.mail, branch.email),
            if (branch.managerName?.isNotEmpty == true)
              _buildInfoRow(LucideIcons.user,
                  'Branch Manager: ${branch.managerName}'),
            if (branch.vehiclesCount > 0) ...[
              const SizedBox(height: AppSpacing.sm),
              _buildInfoRow(LucideIcons.car,
                  '${branch.vehiclesCount} vehicle${branch.vehiclesCount == 1 ? '' : 's'} available'),
            ],
            const SizedBox(height: AppSpacing.xxxl),
            PrimaryButton(
              text: 'Get Directions',
              icon: LucideIcons.navigation,
              onPressed: _openDirections,
            ),
            const SizedBox(height: AppSpacing.md),
            SecondaryButton(
              text: 'View Cars at this Branch',
              icon: LucideIcons.car,
              onPressed: () =>
                  context.push(AppRoutes.browse, extra: branch.id.toString()),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildInfoRow(IconData icon, String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: AppSpacing.md),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(AppSpacing.sm),
            decoration: BoxDecoration(
              color: AppColors.primaryLight,
              borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
            ),
            child: Icon(icon, color: AppColors.primary, size: 20),
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(child: Text(text, style: AppTypography.textTheme.bodyLarge)),
        ],
      ),
    );
  }
}
