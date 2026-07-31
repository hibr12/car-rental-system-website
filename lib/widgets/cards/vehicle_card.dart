import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../models/vehicle_model.dart';
import 'package:shimmer/shimmer.dart';

class VehicleCard extends StatelessWidget {
  final Vehicle vehicle;
  final VoidCallback onTap;
  final bool isHorizontal;
  final double? width;

  const VehicleCard({
    super.key,
    required this.vehicle,
    required this.onTap,
    this.isHorizontal = false,
    this.width,
  });

  @override
  Widget build(BuildContext context) {
    if (isHorizontal) {
      return _buildHorizontalCard(context);
    }
    return _buildVerticalCard(context);
  }

  Widget _buildVerticalCard(BuildContext context) {
    final bool isCompact = width == null; // grid mode

    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: width ?? double.infinity,
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
          border: Border.all(color: AppColors.border),
          boxShadow: [
            BoxShadow(
              color: AppColors.textPrimary.withOpacity(0.04),
              blurRadius: 10,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            // Image Section
            Stack(
              children: [
                ClipRRect(
                  borderRadius: const BorderRadius.vertical(
                      top: Radius.circular(AppSpacing.radiusLg)),
                  child: Hero(
                    tag: 'vehicle_image_${vehicle.id}',
                    child: isCompact
                        ? AspectRatio(
                            aspectRatio: 16 / 10,
                            child: CachedNetworkImage(
                              imageUrl: vehicle.imageUrls.first,
                              fit: BoxFit.cover,
                              placeholder: (context, url) => Shimmer.fromColors(
                                baseColor: AppColors.textTertiary,
                                highlightColor: AppColors.textTertiary,
                                child: Container(color: AppColors.surface),
                              ),
                              errorWidget: (context, url, error) => Container(
                                color: AppColors.textTertiary,
                                child: const Icon(LucideIcons.imageOff,
                                    color: AppColors.textTertiary),
                              ),
                            ),
                          )
                        : CachedNetworkImage(
                            imageUrl: vehicle.imageUrls.first,
                            height: 120,
                            width: double.infinity,
                            fit: BoxFit.cover,
                            placeholder: (context, url) => Shimmer.fromColors(
                              baseColor: AppColors.textTertiary,
                              highlightColor: AppColors.textTertiary,
                              child: Container(
                                  color: AppColors.surface, height: 120),
                            ),
                            errorWidget: (context, url, error) => Container(
                              height: 120,
                              color: AppColors.textTertiary,
                              child: const Icon(LucideIcons.imageOff,
                                  color: AppColors.textTertiary),
                            ),
                          ),
                  ),
                ),
                Positioned(
                  top: AppSpacing.sm,
                  right: AppSpacing.sm,
                  child: Container(
                    padding: const EdgeInsets.all(AppSpacing.sm),
                    decoration: const BoxDecoration(
                      color: AppColors.surface,
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(LucideIcons.heart,
                        size: 18, color: AppColors.textSecondary),
                  ),
                ),
                if (vehicle.category == 'Electric')
                  Positioned(
                    top: AppSpacing.sm,
                    left: AppSpacing.sm,
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: AppColors.secondary,
                        borderRadius:
                            BorderRadius.circular(AppSpacing.radiusSm),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(LucideIcons.zap,
                              size: 12, color: AppColors.surface),
                          const SizedBox(width: AppSpacing.xs),
                          Text('EV',
                              style: AppTypography.textTheme.labelSmall
                                  ?.copyWith(
                                      color: AppColors.surface, fontSize: 10)),
                        ],
                      ),
                    ),
                  ),
              ],
            ),

            // Details Section
            Padding(
              padding:
                  EdgeInsets.all(isCompact ? AppSpacing.sm : AppSpacing.md),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(
                        child: Text(
                          vehicle.fullName,
                          style: isCompact
                              ? AppTypography.textTheme.titleMedium
                              : AppTypography.textTheme.titleLarge,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      const SizedBox(width: AppSpacing.xs),
                      Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(LucideIcons.star,
                              size: 14, color: AppColors.warning),
                          const SizedBox(width: AppSpacing.xs),
                          Text(
                            vehicle.rating.toString(),
                            style: AppTypography.textTheme.labelSmall
                                ?.copyWith(color: AppColors.textPrimary),
                          ),
                        ],
                      ),
                    ],
                  ),
                  const SizedBox(height: AppSpacing.xs),
                  Text(
                    '${vehicle.category} • ${vehicle.seats} Seats',
                    style: AppTypography.textTheme.bodySmall,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  SizedBox(height: isCompact ? AppSpacing.sm : AppSpacing.md),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Flexible(
                        child: RichText(
                          overflow: TextOverflow.ellipsis,
                          text: TextSpan(
                            children: [
                              TextSpan(
                                text: '\$${vehicle.pricePerDay.toInt()}',
                                style: (isCompact
                                        ? AppTypography.textTheme.titleLarge
                                        : AppTypography
                                            .textTheme.headlineMedium)
                                    ?.copyWith(color: AppColors.primary),
                              ),
                              TextSpan(
                                text: ' /d',
                                style: AppTypography.textTheme.bodySmall,
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(width: AppSpacing.xs),
                      Container(
                        padding: EdgeInsets.symmetric(
                          horizontal: isCompact ? 8 : 12,
                          vertical: isCompact ? 4 : 6,
                        ),
                        decoration: BoxDecoration(
                          color: AppColors.primaryLight,
                          borderRadius:
                              BorderRadius.circular(AppSpacing.radiusSm),
                        ),
                        child: Text(
                          'Book',
                          style: AppTypography.textTheme.labelMedium
                              ?.copyWith(color: AppColors.primary),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildHorizontalCard(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        height: 130,
        margin: const EdgeInsets.only(bottom: AppSpacing.md),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
          border: Border.all(color: AppColors.border),
        ),
        child: Row(
          children: [
            // Image
            ClipRRect(
              borderRadius: const BorderRadius.horizontal(
                  left: Radius.circular(AppSpacing.radiusLg)),
              child: Hero(
                tag: 'vehicle_image_${vehicle.id}',
                child: CachedNetworkImage(
                  imageUrl: vehicle.imageUrls.first,
                  width: 125,
                  height: double.infinity,
                  fit: BoxFit.cover,
                  placeholder: (context, url) => Shimmer.fromColors(
                    baseColor: AppColors.textTertiary,
                    highlightColor: AppColors.textTertiary,
                    child: Container(color: AppColors.surface, width: 125),
                  ),
                  errorWidget: (context, url, error) => Container(
                    width: 125,
                    color: AppColors.textTertiary,
                    child: const Icon(LucideIcons.imageOff,
                        color: AppColors.surface),
                  ),
                ),
              ),
            ),

            // Details
            Expanded(
              child: Padding(
                padding: const EdgeInsets.all(AppSpacing.sm),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Expanded(
                          child: Text(
                            vehicle.fullName,
                            style: AppTypography.textTheme.titleMedium,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        const SizedBox(width: AppSpacing.xs),
                        const Icon(LucideIcons.heart,
                            size: 18, color: AppColors.textTertiary),
                      ],
                    ),
                    Text(
                      '${vehicle.transmission} • ${vehicle.fuelType}',
                      style: AppTypography.textTheme.bodySmall,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Flexible(
                          child: RichText(
                            overflow: TextOverflow.ellipsis,
                            text: TextSpan(
                              children: [
                                TextSpan(
                                  text: '\$${vehicle.pricePerDay.toInt()}',
                                  style: AppTypography.textTheme.titleLarge
                                      ?.copyWith(color: AppColors.primary),
                                ),
                                TextSpan(
                                  text: '/day',
                                  style: AppTypography.textTheme.bodySmall,
                                ),
                              ],
                            ),
                          ),
                        ),
                        Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(LucideIcons.star,
                                size: 14, color: AppColors.warning),
                            const SizedBox(width: AppSpacing.xs),
                            Text(
                              vehicle.rating.toString(),
                              style: AppTypography.textTheme.labelSmall
                                  ?.copyWith(color: AppColors.textPrimary),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
