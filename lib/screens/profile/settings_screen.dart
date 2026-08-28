import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:go_router/go_router.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../core/routes/app_routes.dart';

/// About & preferences hub.
///
/// Deliberately minimal: every toggle here must reflect real functionality.
/// The backend has no per-user preference endpoints yet, so fake
/// dark-mode/notification toggles are intentionally NOT offered.
class SettingsScreen extends StatelessWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('Settings')),
      body: ListView(
        padding: const EdgeInsets.symmetric(vertical: AppSpacing.md),
        children: [
          _buildSectionTitle('Preferences'),
          Container(
            color: AppColors.surface,
            child: ListTile(
              leading: const Icon(LucideIcons.banknote, color: AppColors.primary),
              title:
                  Text('Currency', style: AppTypography.textTheme.titleMedium),
              subtitle: Text('Ethiopian Birr (ETB)',
                  style: AppTypography.textTheme.bodyMedium),
              contentPadding: const EdgeInsets.symmetric(
                  horizontal: AppSpacing.pagePadding),
            ),
          ),
          Container(
            color: AppColors.surface,
            child: ListTile(
              leading:
                  const Icon(LucideIcons.creditCard, color: AppColors.primary),
              title: Text('Payments',
                  style: AppTypography.textTheme.titleMedium),
              subtitle: Text(
                  'Secure checkout via Chapa, or cash at any branch',
                  style: AppTypography.textTheme.bodyMedium),
              contentPadding: const EdgeInsets.symmetric(
                  horizontal: AppSpacing.pagePadding),
            ),
          ),
          const Divider(height: AppSpacing.xxl),
          _buildSectionTitle('Legal'),
          _buildNavTile(
            context,
            icon: LucideIcons.fileText,
            title: 'Rental Agreement',
            route: AppRoutes.rentalAgreement,
          ),
          _buildNavTile(
            context,
            icon: LucideIcons.shieldCheck,
            title: 'Insurance Policy',
            route: AppRoutes.insurancePolicy,
          ),
          _buildNavTile(
            context,
            icon: LucideIcons.fileX,
            title: 'Cancellation Policy',
            route: AppRoutes.cancellationPolicy,
          ),
          _buildNavTile(
            context,
            icon: LucideIcons.fileBadge,
            title: 'Driver Requirements',
            route: AppRoutes.driverRequirements,
          ),
          const Divider(height: AppSpacing.xxl),
          _buildSectionTitle('Support'),
          _buildNavTile(
            context,
            icon: LucideIcons.helpCircle,
            title: 'Help Center',
            route: AppRoutes.support,
          ),
          const Divider(height: AppSpacing.xxl),
          Padding(
            padding: const EdgeInsets.all(AppSpacing.pagePadding),
            child: Text(
              'Apex Rentals v1.0.0',
              textAlign: TextAlign.center,
              style: AppTypography.textTheme.bodySmall
                  ?.copyWith(color: AppColors.textTertiary),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.symmetric(
          horizontal: AppSpacing.pagePadding, vertical: AppSpacing.sm),
      child: Text(title, style: AppTypography.textTheme.headlineMedium),
    );
  }

  Widget _buildNavTile(BuildContext context,
      {required IconData icon,
      required String title,
      required String route}) {
    return Container(
      color: AppColors.surface,
      child: ListTile(
        leading: Icon(icon, color: AppColors.primary),
        title: Text(title, style: AppTypography.textTheme.titleMedium),
        trailing: const Icon(LucideIcons.chevronRight,
            size: 20, color: AppColors.textTertiary),
        onTap: () => context.push(route),
        contentPadding:
            const EdgeInsets.symmetric(horizontal: AppSpacing.pagePadding),
      ),
    );
  }
}
