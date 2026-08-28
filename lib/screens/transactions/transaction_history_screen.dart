import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:go_router/go_router.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../core/routes/app_routes.dart';
import '../../core/utils/formatters.dart';
import '../../models/transaction_model.dart';
import '../../widgets/states/empty_state_widget.dart';
import '../../widgets/states/error_state_widget.dart';

import '../../data/repositories/transaction_repository.dart';

class TransactionHistoryScreen extends StatefulWidget {
  const TransactionHistoryScreen({super.key});

  @override
  State<TransactionHistoryScreen> createState() =>
      _TransactionHistoryScreenState();
}

class _TransactionHistoryScreenState extends State<TransactionHistoryScreen> {
  List<Transaction> _transactions = [];
  bool _isLoading = true;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _fetchTransactions();
  }

  Future<void> _fetchTransactions() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });
    final res = await TransactionRepository.instance.getUserTransactions();
    if (mounted) {
      setState(() {
        if (res.success) {
          _transactions = res.data ?? [];
          _errorMessage = null;
        } else {
          _transactions = [];
          _errorMessage =
              res.error?.friendlyMessage ?? 'Failed to load transactions';
        }
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('Transaction History')),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _errorMessage != null
              ? ErrorStateWidget(
                  message: _errorMessage!,
                  onRetry: _fetchTransactions,
                )
              : _transactions.isEmpty
                  ? const EmptyStateWidget(
                      icon: LucideIcons.receipt,
                      title: 'No Transactions Yet',
                      message:
                          'Your payment history will appear here once you make a booking.',
                    )
                  : RefreshIndicator(
                      onRefresh: _fetchTransactions,
                      child: ListView.separated(
                        padding: const EdgeInsets.all(AppSpacing.pagePadding),
                        itemCount: _transactions.length,
                        separatorBuilder: (_, __) =>
                            const SizedBox(height: AppSpacing.md),
                        itemBuilder: (context, index) {
                          final txn = _transactions[index];
                          return _buildTransactionCard(context, txn);
                        },
                      ),
                    ),
    );
  }

  Widget _buildTransactionCard(BuildContext context, Transaction txn) {
    IconData icon;
    Color iconColor;

    if (txn.status.isRefundFamily) {
      icon = LucideIcons.arrowDownLeft;
      iconColor = AppColors.success;
    } else if (txn.status.isSuccessful) {
      icon = LucideIcons.arrowUpRight;
      iconColor = AppColors.primary;
    } else {
      switch (txn.status) {
        case TransactionStatus.failed:
        case TransactionStatus.invalid:
        case TransactionStatus.cancelled:
          icon = LucideIcons.alertCircle;
          iconColor = AppColors.error;
          break;
        default:
          icon = LucideIcons.clock;
          iconColor = AppColors.warning;
      }
    }

    return InkWell(
      onTap: () => context.push(AppRoutes.invoiceDetail, extra: txn),
      borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
      child: Container(
        padding: const EdgeInsets.all(AppSpacing.md),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
          border: Border.all(color: AppColors.border),
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(AppSpacing.sm),
              decoration: BoxDecoration(
                color: iconColor.withOpacity(0.1),
                borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
              ),
              child: Icon(icon, color: iconColor),
            ),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(txn.vehicleName,
                      style: AppTypography.textTheme.titleMedium,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis),
                  const SizedBox(height: AppSpacing.xs),
                  Text(
                    '${txn.paymentMethod} • ${Formatters.date(txn.date)}',
                    style: AppTypography.textTheme.bodySmall,
                  ),
                ],
              ),
            ),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(
                  Formatters.etb(txn.amount),
                  style: AppTypography.textTheme.titleMedium?.copyWith(
                    color: txn.status.isSuccessful
                        ? AppColors.textPrimary
                        : AppColors.textSecondary,
                  ),
                ),
                const SizedBox(height: AppSpacing.xs),
                Text(
                  txn.status.label,
                  style: AppTypography.textTheme.labelSmall?.copyWith(
                    color: txn.status.isSuccessful
                        ? AppColors.success
                        : AppColors.textSecondary,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
