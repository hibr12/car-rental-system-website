import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:intl/intl.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../models/transaction_model.dart';
import '../../widgets/buttons/app_buttons.dart';
import 'package:go_router/go_router.dart';
import '../../core/routes/app_routes.dart';

class InvoiceDetailScreen extends StatelessWidget {
  final Transaction transaction;

  const InvoiceDetailScreen({super.key, required this.transaction});

  @override
  Widget build(BuildContext context) {
    final dateFormat = DateFormat('MMM d, yyyy • h:mm a');

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('Invoice Details')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(AppSpacing.pagePadding),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            const SizedBox(height: AppSpacing.xl),
            Container(
              padding: const EdgeInsets.all(AppSpacing.md),
              decoration: BoxDecoration(
                color: AppColors.success.withOpacity(0.1),
                shape: BoxShape.circle,
              ),
              child: const Icon(LucideIcons.checkCircle,
                  size: 48, color: AppColors.success),
            ),
            const SizedBox(height: AppSpacing.md),
            Text('\$${transaction.amount.toStringAsFixed(2)}',
                style: AppTypography.textTheme.displayMedium),
            const SizedBox(height: AppSpacing.xs),
            Text(transaction.statusLabel,
                style: AppTypography.textTheme.titleMedium
                    ?.copyWith(color: AppColors.success)),
            const SizedBox(height: AppSpacing.xxxl),
            Container(
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
                  _buildDetailRow('Date', dateFormat.format(transaction.date)),
                  const Divider(height: AppSpacing.lg),
                  _buildDetailRow('Payment Method', transaction.paymentMethod),
                  const Divider(height: AppSpacing.lg),
                  _buildDetailRow('Type', transaction.typeLabel),
                  const Divider(height: AppSpacing.lg),
                  _buildDetailRow('Booking Ref', transaction.bookingReference),
                  const Divider(height: AppSpacing.lg),
                  _buildDetailRow('Vehicle', transaction.vehicleName),
                ],
              ),
            ),
            const SizedBox(height: AppSpacing.xxxl),
            PrimaryButton(
              text: 'Download PDF Receipt',
              icon: LucideIcons.download,
              onPressed: () {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                      content:
                          Text('Downloading receipt... (Not implemented yet)')),
                );
              },
            ),
            const SizedBox(height: AppSpacing.md),
            SecondaryButton(
              text: 'Report an Issue',
              icon: LucideIcons.alertCircle,
              onPressed: () => context.push(AppRoutes.chat),
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
