import 'package:flutter/material.dart';
import 'package:mobile/core/colors/app_colors.dart';
import 'package:shimmer/shimmer.dart';
import '../../core/spacing/app_spacing.dart';

class AppLoadingSkeleton extends StatelessWidget {
  final double height;
  final double width;
  final double borderRadius;
  final EdgeInsetsGeometry? margin;

  const AppLoadingSkeleton({
    super.key,
    this.height = 20,
    this.width = double.infinity,
    this.borderRadius = AppSpacing.radiusSm,
    this.margin,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: margin ?? EdgeInsets.zero,
      child: Shimmer.fromColors(
        baseColor: AppColors.textTertiary,
        highlightColor: AppColors.textTertiary,
        child: Container(
          height: height,
          width: width,
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(borderRadius),
          ),
        ),
      ),
    );
  }

  // Pre-configured skeletons
  static Widget card({double height = 200}) {
    return AppLoadingSkeleton(
      height: height,
      borderRadius: AppSpacing.radiusLg,
      margin: const EdgeInsets.only(bottom: AppSpacing.md),
    );
  }

  static Widget list({int count = 5}) {
    return ListView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: count,
      padding: const EdgeInsets.all(AppSpacing.pagePadding),
      itemBuilder: (context, index) {
        return card(height: 120);
      },
    );
  }

  static Widget grid({int count = 6}) {
    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      padding: const EdgeInsets.all(AppSpacing.pagePadding),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        childAspectRatio: 0.78,
        crossAxisSpacing: AppSpacing.md,
        mainAxisSpacing: AppSpacing.md,
      ),
      itemCount: count,
      itemBuilder: (context, index) {
        return card(height: 200);
      },
    );
  }
}
