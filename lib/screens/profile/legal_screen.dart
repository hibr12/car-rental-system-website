import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:go_router/go_router.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../core/routes/app_routes.dart';

class LegalScreen extends StatelessWidget {
  const LegalScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('Terms & Privacy')),
      body: ListView(
        padding: const EdgeInsets.symmetric(vertical: AppSpacing.md),
        children: [
          _buildMenuSection(
            'Agreements & Policies',
            [
              _MenuItem(
                icon: LucideIcons.fileText,
                title: 'Rental Agreement',
                subtitle: 'Master terms and conditions',
                onTap: () => context.push(AppRoutes.rentalAgreement),
              ),
              _MenuItem(
                icon: LucideIcons.shield,
                title: 'Insurance Policy',
                subtitle: 'Coverage plans and protection',
                onTap: () => context.push(AppRoutes.insurancePolicy),
              ),
              _MenuItem(
                icon: LucideIcons.xCircle,
                title: 'Cancellation Policy',
                subtitle: 'Refunds and cancellation fees',
                onTap: () => context.push(AppRoutes.cancellationPolicy),
              ),
              _MenuItem(
                icon: LucideIcons.userCheck,
                title: 'Driver Requirements',
                subtitle: 'Age, license, and rules',
                onTap: () => context.push(AppRoutes.driverRequirements),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.xxl),
          Padding(
            padding:
                const EdgeInsets.symmetric(horizontal: AppSpacing.pagePadding),
            child: Text(
              'Privacy Policy\n\nBy using DriveEase, you consent to our data collection practices as detailed in our full Privacy Policy document available on our website. We collect necessary information to facilitate rentals and verify identity.',
              style: AppTypography.textTheme.bodyMedium
                  ?.copyWith(color: AppColors.textSecondary),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMenuSection(String title, List<_MenuItem> items) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(
              horizontal: AppSpacing.pagePadding, vertical: AppSpacing.sm),
          child: Text(title, style: AppTypography.textTheme.headlineMedium),
        ),
        Container(
          color: AppColors.surface,
          child: Column(
            children: items.map((item) {
              return ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(AppSpacing.sm),
                  decoration: BoxDecoration(
                    color: AppColors.background,
                    borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
                  ),
                  child: Icon(item.icon, size: 20, color: AppColors.primary),
                ),
                title: Text(item.title,
                    style: AppTypography.textTheme.titleMedium),
                subtitle: Text(item.subtitle,
                    style: AppTypography.textTheme.bodyMedium),
                trailing: const Icon(LucideIcons.chevronRight,
                    size: 20, color: AppColors.textTertiary),
                onTap: item.onTap,
                contentPadding: const EdgeInsets.symmetric(
                    horizontal: AppSpacing.pagePadding, vertical: 8),
              );
            }).toList(),
          ),
        ),
      ],
    );
  }
}

class _MenuItem {
  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

  _MenuItem(
      {required this.icon,
      required this.title,
      required this.subtitle,
      required this.onTap});
}
