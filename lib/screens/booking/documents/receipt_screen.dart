import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../../../core/colors/app_colors.dart';
import '../../../../core/spacing/app_spacing.dart';
import '../../../../core/typography/app_typography.dart';
import '../../../../models/transaction_model.dart';

class ReceiptScreen extends StatelessWidget {
  final Transaction transaction;

  const ReceiptScreen({super.key, required this.transaction});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Payment Receipt'),
        actions: [
          IconButton(icon: const Icon(LucideIcons.download), onPressed: () {}),
          IconButton(icon: const Icon(LucideIcons.share2), onPressed: () {}),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(AppSpacing.pagePadding),
        child: Container(
          width: double.infinity,
          padding: const EdgeInsets.all(AppSpacing.xl),
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
            border: Border.all(color: AppColors.border),
          ),
          child: Column(
            children: [
              const Icon(LucideIcons.checkCircle2,
                  size: 64, color: AppColors.success),
              const SizedBox(height: AppSpacing.md),
              Text('Payment Successful',
                  style: AppTypography.textTheme.headlineMedium),
              const SizedBox(height: AppSpacing.sm),
              Text(
                'Transaction #${transaction.id}',
                style: AppTypography.textTheme.bodyMedium
                    ?.copyWith(color: AppColors.textSecondary),
              ),
              const Padding(
                padding: EdgeInsets.symmetric(vertical: AppSpacing.xl),
                child: Divider(),
              ),
              _buildRow(
                  'Amount Paid', '\$${transaction.amount.toStringAsFixed(2)}',
                  isBold: true),
              const SizedBox(height: AppSpacing.md),
              _buildRow('Date', transaction.date.toString().substring(0, 16)),
              const SizedBox(height: AppSpacing.md),
              _buildRow('Payment Method', transaction.paymentMethod),
              const SizedBox(height: AppSpacing.md),
              _buildRow('Vehicle', transaction.vehicleName),
              const SizedBox(height: AppSpacing.md),
              _buildRow('Booking Ref', transaction.bookingReference),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildRow(String label, String value, {bool isBold = false}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label,
            style: AppTypography.textTheme.bodyMedium
                ?.copyWith(color: AppColors.textSecondary)),
        Text(
          value,
          style: isBold
              ? AppTypography.textTheme.titleLarge
              : AppTypography.textTheme.bodyLarge,
        ),
      ],
    );
  }
}
