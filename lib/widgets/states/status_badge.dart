import 'package:flutter/material.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import 'package:lucide_icons/lucide_icons.dart';

enum BadgeStatus { pending, approved, rejected, active, completed, cancelled }

class StatusBadge extends StatelessWidget {
  final String label;
  final BadgeStatus status;

  const StatusBadge({
    super.key,
    required this.label,
    required this.status,
  });

  @override
  Widget build(BuildContext context) {
    Color bgColor;
    Color textColor;
    IconData? icon;

    switch (status) {
      case BadgeStatus.pending:
        bgColor = AppColors.warning.withOpacity(0.1);
        textColor = AppColors.warning;
        icon = LucideIcons.clock;
        break;
      case BadgeStatus.approved:
      case BadgeStatus.completed:
        bgColor = AppColors.success.withOpacity(0.1);
        textColor = AppColors.success;
        icon = LucideIcons.checkCircle2;
        break;
      case BadgeStatus.rejected:
      case BadgeStatus.cancelled:
        bgColor = AppColors.error.withOpacity(0.1);
        textColor = AppColors.error;
        icon = LucideIcons.xCircle;
        break;
      case BadgeStatus.active:
        bgColor = AppColors.primary.withOpacity(0.1);
        textColor = AppColors.primary;
        icon = LucideIcons.zap;
        break;
    }

    return Container(
      padding:
          const EdgeInsets.symmetric(horizontal: AppSpacing.sm, vertical: 4),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: textColor),
          const SizedBox(width: AppSpacing.xs),
          Text(
            label,
            style: AppTypography.textTheme.labelMedium?.copyWith(
              color: textColor,
              fontWeight: FontWeight.bold,
            ),
          ),
        ],
      ),
    );
  }
}
