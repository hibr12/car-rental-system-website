import 'package:flutter/material.dart';
import 'package:shimmer/shimmer.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';

class VehicleSkeleton extends StatelessWidget {
  final bool isHorizontal;

  const VehicleSkeleton({super.key, this.isHorizontal = false});

  @override
  Widget build(BuildContext context) {
    if (isHorizontal) return _buildHorizontal(context);
    return _buildVertical(context);
  }

  Widget _buildVertical(BuildContext context) {
    return Container(
      width: 260,
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Shimmer.fromColors(
            baseColor: AppColors.textTertiary,
            highlightColor: AppColors.textTertiary,
            child: Container(
              height: 160,
              decoration: const BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.vertical(
                    top: Radius.circular(AppSpacing.radiusLg)),
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(AppSpacing.md),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _buildSkeletonBox(width: 150, height: 20),
                const SizedBox(height: AppSpacing.sm),
                _buildSkeletonBox(width: 100, height: 16),
                const SizedBox(height: AppSpacing.md),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    _buildSkeletonBox(width: 80, height: 24),
                    _buildSkeletonBox(
                        width: 80,
                        height: 32,
                        borderRadius: AppSpacing.radiusSm),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHorizontal(BuildContext context) {
    return Container(
      height: 120,
      margin: const EdgeInsets.only(bottom: AppSpacing.md),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        children: [
          Shimmer.fromColors(
            baseColor: AppColors.textTertiary,
            highlightColor: AppColors.textTertiary,
            child: Container(
              width: 140,
              decoration: const BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.horizontal(
                    left: Radius.circular(AppSpacing.radiusLg)),
              ),
            ),
          ),
          Expanded(
            child: Padding(
              padding: const EdgeInsets.all(AppSpacing.sm),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                children: [
                  _buildSkeletonBox(width: double.infinity, height: 20),
                  _buildSkeletonBox(width: 100, height: 14),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      _buildSkeletonBox(width: 60, height: 20),
                      _buildSkeletonBox(width: 40, height: 16),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSkeletonBox(
      {required double width,
      required double height,
      double borderRadius = 4.0}) {
    return Shimmer.fromColors(
      baseColor: AppColors.textTertiary,
      highlightColor: AppColors.textTertiary,
      child: Container(
        width: width,
        height: height,
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(borderRadius),
        ),
      ),
    );
  }
}
