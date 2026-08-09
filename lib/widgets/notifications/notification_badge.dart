import 'package:flutter/material.dart';
import 'package:mobile/core/spacing/app_spacing.dart';
import '../../core/colors/app_colors.dart';
import '../../core/typography/app_typography.dart';

class NotificationBadge extends StatelessWidget {
  final Widget child;
  final int unreadCount;
  final bool showBadge;

  const NotificationBadge({
    super.key,
    required this.child,
    this.unreadCount = 0,
    this.showBadge = false,
  });

  @override
  Widget build(BuildContext context) {
    return Stack(
      clipBehavior: Clip.none,
      children: [
        child,
        if (showBadge || unreadCount > 0)
          Positioned(
            right: -4,
            top: -4,
            child: Container(
              padding: const EdgeInsets.all(AppSpacing.xs),
              decoration: BoxDecoration(
                color: AppColors.error,
                shape: BoxShape.circle,
                border: Border.all(color: AppColors.background, width: 2),
              ),
              constraints: const BoxConstraints(
                minWidth: 16,
                minHeight: 16,
              ),
              child: unreadCount > 0
                  ? Center(
                      child: Text(
                        unreadCount > 99 ? '99+' : unreadCount.toString(),
                        style: AppTypography.textTheme.labelSmall?.copyWith(
                          color: AppColors.surface,
                          fontSize: 8,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    )
                  : const SizedBox(),
            ),
          ),
      ],
    );
  }
}
