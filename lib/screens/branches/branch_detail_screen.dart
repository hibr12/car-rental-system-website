import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../models/branch_model.dart';
import '../../widgets/buttons/app_buttons.dart';

class BranchDetailScreen extends StatelessWidget {
  final Branch branch;

  const BranchDetailScreen({super.key, required this.branch});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            expandedHeight: 250,
            pinned: true,
            flexibleSpace: FlexibleSpaceBar(
              background: Image.network(
                branch.imageUrl,
                fit: BoxFit.cover,
              ),
            ),
          ),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(AppSpacing.pagePadding),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(
                        child: Text(branch.name,
                            style: AppTypography.textTheme.displaySmall),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 12, vertical: 6),
                        decoration: BoxDecoration(
                          color: branch.isOpen
                              ? AppColors.success.withOpacity(0.1)
                              : AppColors.error.withOpacity(0.1),
                          borderRadius:
                              BorderRadius.circular(AppSpacing.radiusSm),
                        ),
                        child: Text(
                          branch.isOpen ? 'Open Now' : 'Closed',
                          style: AppTypography.textTheme.titleSmall?.copyWith(
                            color: branch.isOpen
                                ? AppColors.success
                                : AppColors.error,
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: AppSpacing.lg),
                  _buildInfoRow(LucideIcons.mapPin, branch.address),
                  _buildInfoRow(LucideIcons.phone, branch.phone),
                  _buildInfoRow(LucideIcons.mail, branch.email),
                  const SizedBox(height: AppSpacing.xxxl),
                  Text('Opening Hours',
                      style: AppTypography.textTheme.headlineMedium),
                  const SizedBox(height: AppSpacing.md),
                  Text(branch.openingHours,
                      style: AppTypography.textTheme.bodyLarge),
                  const SizedBox(height: AppSpacing.xxxl),
                  Text('Available Services',
                      style: AppTypography.textTheme.headlineMedium),
                  const SizedBox(height: AppSpacing.md),
                  _buildServiceChip('24/7 Drop-off'),
                  _buildServiceChip('Luxury Vehicles Available'),
                  _buildServiceChip('Free Wi-Fi'),
                  const SizedBox(height: AppSpacing.xxxl),
                  PrimaryButton(
                    text: 'Get Directions',
                    icon: LucideIcons.navigation,
                    onPressed: () {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(
                            content:
                                Text('Opening maps... (Not implemented yet)')),
                      );
                    },
                  ),
                  const SizedBox(height: AppSpacing.md),
                  SecondaryButton(
                    text: 'View Cars at this Branch',
                    onPressed: () {
                      Navigator.pop(
                          context); // Go back and maybe select this branch in search
                    },
                  ),
                ],
              ),
            ),
          ),
        ],
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

  Widget _buildServiceChip(String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: AppSpacing.sm),
      child: Row(
        children: [
          const Icon(LucideIcons.checkCircle,
              color: AppColors.success, size: 20),
          const SizedBox(width: AppSpacing.sm),
          Text(text, style: AppTypography.textTheme.bodyLarge),
        ],
      ),
    );
  }
}
