import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../images/app_network_image.dart';

enum MessageStatus { sending, sent, delivered, read, failed }

class ChatBubbleWidget extends StatelessWidget {
  final String text;
  final bool isMe;
  final String? time;
  final MessageStatus? status;
  final String? attachmentUrl;

  const ChatBubbleWidget({
    super.key,
    required this.text,
    required this.isMe,
    this.time,
    this.status,
    this.attachmentUrl,
  });

  @override
  Widget build(BuildContext context) {
    return Align(
      alignment: isMe ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.only(bottom: AppSpacing.md),
        constraints: BoxConstraints(
          maxWidth: MediaQuery.of(context).size.width * 0.75,
        ),
        decoration: BoxDecoration(
          color: isMe ? AppColors.primary : AppColors.surface,
          borderRadius: BorderRadius.circular(AppSpacing.radiusLg).copyWith(
            bottomRight: isMe ? const Radius.circular(0) : null,
            bottomLeft: !isMe ? const Radius.circular(0) : null,
          ),
          border: isMe ? null : Border.all(color: AppColors.border),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (attachmentUrl != null)
              ClipRRect(
                borderRadius: const BorderRadius.vertical(
                    top: Radius.circular(AppSpacing.radiusLg)),
                child: AppNetworkImage(
                  imageUrl: attachmentUrl!,
                  width: double.infinity,
                  height: 150,
                ),
              ),
            Padding(
              padding: const EdgeInsets.all(AppSpacing.md),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    text,
                    style: AppTypography.textTheme.bodyMedium?.copyWith(
                      color: isMe ? AppColors.surface : AppColors.textPrimary,
                    ),
                  ),
                  const SizedBox(height: AppSpacing.xs),
                  Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      if (time != null)
                        Text(
                          time!,
                          style: AppTypography.textTheme.labelSmall?.copyWith(
                            color: isMe
                                ? AppColors.textSecondary
                                : AppColors.textTertiary,
                            fontSize: 10,
                          ),
                        ),
                      if (isMe && status != null) ...[
                        const SizedBox(width: AppSpacing.xs),
                        _buildStatusIcon(),
                      ],
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

  Widget _buildStatusIcon() {
    IconData icon;
    Color color = AppColors.textSecondary;

    switch (status!) {
      case MessageStatus.sending:
        icon = LucideIcons.clock;
        break;
      case MessageStatus.sent:
        icon = LucideIcons.check;
        break;
      case MessageStatus.delivered:
        icon = LucideIcons.checkCheck;
        break;
      case MessageStatus.read:
        icon = LucideIcons.checkCheck;
        color = AppColors.primary;
        break;
      case MessageStatus.failed:
        icon = LucideIcons.alertCircle;
        color = AppColors.error;
        break;
    }

    return Icon(icon, size: 12, color: color);
  }
}

class TypingIndicatorWidget extends StatelessWidget {
  const TypingIndicatorWidget({super.key});

  @override
  Widget build(BuildContext context) {
    return Align(
      alignment: Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.only(bottom: AppSpacing.md),
        padding: const EdgeInsets.symmetric(
            horizontal: AppSpacing.lg, vertical: AppSpacing.md),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
          border: Border.all(color: AppColors.border),
        ),
        child: const Text('Typing...',
            style: TextStyle(
                fontStyle: FontStyle.italic, color: AppColors.textTertiary)),
      ),
    );
  }
}
