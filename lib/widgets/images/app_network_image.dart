import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:shimmer/shimmer.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';

class AppNetworkImage extends StatelessWidget {
  final String imageUrl;
  final double? width;
  final double? height;
  final BoxFit fit;
  final Widget? errorWidget;
  final BorderRadius? borderRadius;

  const AppNetworkImage({
    super.key,
    required this.imageUrl,
    this.width,
    this.height,
    this.fit = BoxFit.cover,
    this.errorWidget,
    this.borderRadius,
  });

  @override
  Widget build(BuildContext context) {
    final imageWidget = CachedNetworkImage(
      imageUrl: imageUrl,
      width: width,
      height: height,
      fit: fit,
      placeholder: (context, url) => Shimmer.fromColors(
        baseColor: AppColors.border,
        highlightColor: AppColors.surface,
        child: Container(
          width: width,
          height: height,
          color: AppColors.surface,
        ),
      ),
      errorWidget: (context, url, error) =>
          errorWidget ??
          Container(
            width: width,
            height: height,
            color: AppColors.surface,
            child: const Center(
              child: Icon(
                LucideIcons.imageOff,
                color: AppColors.textTertiary,
              ),
            ),
          ),
    );

    if (borderRadius != null) {
      return ClipRRect(
        borderRadius: borderRadius!,
        child: imageWidget,
      );
    }

    return imageWidget;
  }
}

class AppNetworkAvatar extends StatelessWidget {
  final String imageUrl;
  final double radius;

  const AppNetworkAvatar({
    super.key,
    required this.imageUrl,
    this.radius = 24.0,
  });

  @override
  Widget build(BuildContext context) {
    return ClipOval(
      child: AppNetworkImage(
        imageUrl: imageUrl,
        width: radius * 2,
        height: radius * 2,
        fit: BoxFit.cover,
        errorWidget: Container(
          width: radius * 2,
          height: radius * 2,
          color: AppColors.primaryLight,
          child: const Center(
            child: Icon(
              LucideIcons.user,
              color: AppColors.primary,
            ),
          ),
        ),
      ),
    );
  }
}
