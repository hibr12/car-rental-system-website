import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../buttons/app_buttons.dart';

class LocationSelectorWidget extends StatelessWidget {
  final String title;
  final String? location;
  final VoidCallback onTap;
  final bool isLoading;

  const LocationSelectorWidget({
    super.key,
    required this.title,
    this.location,
    required this.onTap,
    this.isLoading = false,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
      child: Container(
        padding: const EdgeInsets.all(AppSpacing.md),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
          border: Border.all(color: AppColors.border),
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(AppSpacing.sm),
              decoration: BoxDecoration(
                color: AppColors.primaryLight,
                borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
              ),
              child: const Icon(LucideIcons.mapPin,
                  color: AppColors.primary, size: 20),
            ),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title,
                      style: AppTypography.textTheme.bodySmall
                          ?.copyWith(color: AppColors.textTertiary)),
                  const SizedBox(height: AppSpacing.xs),
                  if (isLoading)
                    const SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(
                          strokeWidth: 2, color: AppColors.primary),
                    )
                  else
                    Text(
                      location ?? 'Select Location',
                      style: AppTypography.textTheme.titleMedium?.copyWith(
                        color: location == null
                            ? AppColors.textTertiary
                            : AppColors.textPrimary,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                ],
              ),
            ),
            const Icon(LucideIcons.chevronRight, color: AppColors.textTertiary),
          ],
        ),
      ),
    );
  }
}

class LocationPermissionPlaceholder extends StatelessWidget {
  final VoidCallback onRequestPermission;

  const LocationPermissionPlaceholder({
    super.key,
    required this.onRequestPermission,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(AppSpacing.xl),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        children: [
          const Icon(LucideIcons.map, size: 48, color: AppColors.textTertiary),
          const SizedBox(height: AppSpacing.md),
          Text(
            'Location Access Required',
            style: AppTypography.textTheme.titleLarge,
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: AppSpacing.sm),
          Text(
            'We need your location to show nearby branches and available cars.',
            style: AppTypography.textTheme.bodyMedium
                ?.copyWith(color: AppColors.textSecondary),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: AppSpacing.lg),
          PrimaryButton(
            text: 'Enable Location',
            onPressed: onRequestPermission,
          ),
        ],
      ),
    );
  }
}

class MapPreviewPlaceholder extends StatelessWidget {
  final VoidCallback? onTap;

  const MapPreviewPlaceholder({
    super.key,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        height: 150,
        width: double.infinity,
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
          border: Border.all(color: AppColors.border),
        ),
        child: const Stack(
          alignment: Alignment.center,
          children: [
            // Mock map background pattern
            Opacity(
              opacity: 0.1,
              child: GridPaper(
                color: AppColors.primary,
                interval: 20,
                divisions: 1,
                subdivisions: 1,
                child:
                    SizedBox(width: double.infinity, height: double.infinity),
              ),
            ),
            Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(LucideIcons.map, size: 32, color: AppColors.primary),
                SizedBox(height: AppSpacing.sm),
                Text('Tap to view map',
                    style: TextStyle(color: AppColors.textPrimary)),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
