import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../core/utils/formatters.dart';
import '../../models/transaction_model.dart';
import '../../widgets/buttons/app_buttons.dart';
import 'package:go_router/go_router.dart';
import '../../core/routes/app_routes.dart';

class InvoiceDetailScreen extends StatelessWidget {
  final Transaction transaction;

  const InvoiceDetailScreen({super.key, required this.transaction});

  Color get _statusColor {
    if (transaction.status.isSuccessful) return AppColors.success;
    if (transaction.status.isRefundFamily) return AppColors.info;
    switch (transaction.status) {
      case TransactionStatus.failed:
      case TransactionStatus.invalid:
      case TransactionStatus.cancelled:
        return AppColors.error;
      default:
        return AppColors.warning;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('Payment Details')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(AppSpacing.pagePadding),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            const SizedBox(height: AppSpacing.xl),
            Container(
              padding: const EdgeInsets.all(AppSpacing.md),
              decoration: BoxDecoration(
                color: _statusColor.withOpacity(0.1),
                shape: BoxShape.circle,
              ),
              child: Icon(
                transaction.status.isSuccessful
                    ? LucideIcons.checkCircle
                    : (transaction.status.isRefundFamily
                        ? LucideIcons.rotateCcw
                        : LucideIcons.clock),
                size: 48,
                color: _statusColor,
              ),
            ),
            const SizedBox(height: AppSpacing.md),
            Text(Formatters.etb(transaction.amount),
                style: AppTypography.textTheme.displayMedium),
            const SizedBox(height: AppSpacing.xs),
            Text(transaction.status.label,
                style: AppTypography.textTheme.titleMedium
                    ?.copyWith(color: _statusColor)),
            const SizedBox(height: AppSpacing.xxxl),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(AppSpacing.lg),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
                border: Border.all(color: AppColors.border),
              ),
              child: Column(
                children: [
                  _buildDetailRow('Transaction ID', transaction.id),
                  const Divider(height: AppSpacing.lg),
                  _buildDetailRow(
                      'Date', Formatters.dateTime(transaction.date)),
                  const Divider(height: AppSpacing.lg),
                  _buildDetailRow('Payment Method', transaction.paymentMethod),
                  const Divider(height: AppSpacing.lg),
                  if (transaction.transactionReference?.isNotEmpty == true) ...[
                    _buildDetailRow(
                        'Reference', transaction.transactionReference!),
                    const Divider(height: AppSpacing.lg),
                  ],
                  if (transaction.paidAt != null) ...[
                    _buildDetailRow('Paid At', Formatters.dateTime(transaction.paidAt)),
                    const Divider(height: AppSpacing.lg),
                  ],
                  if (transaction.failureReason?.isNotEmpty == true) ...[
                    _buildDetailRow('Failure Reason', transaction.failureReason!),
                    const Divider(height: AppSpacing.lg),
                  ],
                  _buildDetailRow('Booking Ref', transaction.bookingReference),
                  const Divider(height: AppSpacing.lg),
                  _buildDetailRow('Vehicle', transaction.vehicleName),
                ],
              ),
            ),
            // No PDF download exists on the backend — the button was removed
            // rather than faked. Receipt details are shown above.
            const SizedBox(height: AppSpacing.xxxl),
            SecondaryButton(
              text: 'Report an Issue',
              icon: LucideIcons.alertCircle,
              onPressed: () => context.push(AppRoutes.support),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDetailRow(String label, String value) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label,
            style: AppTypography.textTheme.bodyLarge
                ?.copyWith(color: AppColors.textSecondary)),
        const SizedBox(width: AppSpacing.md),
        Expanded(
          child: Text(
            value,
            style: AppTypography.textTheme.titleMedium,
            textAlign: TextAlign.end,
          ),
        ),
      ],
    );
  }
}
