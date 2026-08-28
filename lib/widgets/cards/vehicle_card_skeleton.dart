import 'package:flutter/material.dart';
import 'package:shimmer/shimmer.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';

/// Shimmer placeholder shown while vehicle data loads — mirrors the card
/// geometry so content appears to settle into place instead of popping.
class VehicleCardSkeleton extends StatelessWidget {
  final bool isHorizontal;
  final double? width;

  const VehicleCardSkeleton({super.key, this.isHorizontal = false, this.width});

  @override
  Widget build(BuildContext context) {
    Widget line(double w, double h) => Container(
          width: w,
          height: h,
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
          ),
        );

    return Container(
      width: width ?? double.infinity,
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
        border: Border.all(color: AppColors.border),
      ),
      child: Shimmer.fromColors(
        baseColor: AppColors.border,
        highlightColor: AppColors.backgroundSecondary,
        child: isHorizontal
            ? Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    width: 125,
                    height: 128,
                    color: AppColors.surface,
                  ),
                  Expanded(
                    child: Padding(
                      padding: const EdgeInsets.all(AppSpacing.md),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          line(120, 14),
                          const SizedBox(height: AppSpacing.sm),
                          line(90, 12),
                          const Spacer(),
                          line(80, 18),
                        ],
                      ),
                    ),
                  ),
                ],
              )
            : Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    height: 120,
                    width: double.infinity,
                    color: AppColors.surface,
                  ),
                  Padding(
                    padding: const EdgeInsets.all(AppSpacing.md),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        line(130, 14),
                        const SizedBox(height: AppSpacing.sm),
                        line(100, 11),
                        const SizedBox(height: AppSpacing.md),
                        line(70, 18),
                      ],
                    ),
                  ),
                ],
              ),
      ),
    );
  }
}
