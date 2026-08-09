import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:go_router/go_router.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../core/routes/app_routes.dart';
import '../../models/payment_method_model.dart';

class PaymentMethodsScreen extends StatefulWidget {
  const PaymentMethodsScreen({super.key});

  @override
  State<PaymentMethodsScreen> createState() => _PaymentMethodsScreenState();
}

class _PaymentMethodsScreenState extends State<PaymentMethodsScreen> {
  late List<PaymentMethod> _methods;

  @override
  void initState() {
    super.initState();
    _methods = [];
  }

  IconData _getCardIcon(PaymentMethodType type) {
    switch (type) {
      case PaymentMethodType.visa:
      case PaymentMethodType.mastercard:
      case PaymentMethodType.amex:
        return LucideIcons.creditCard;
      case PaymentMethodType.paypal:
        return LucideIcons.wallet;
      case PaymentMethodType.applePay:
        return LucideIcons.smartphone;
      case PaymentMethodType.googlePay:
        return LucideIcons.smartphone;
    }
  }

  void _deleteMethod(int index) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Remove Payment Method'),
        content: Text(
            'Remove ${_methods[index].displayName} from your saved methods?'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx), child: const Text('Cancel')),
          TextButton(
            onPressed: () {
              setState(() => _methods.removeAt(index));
              Navigator.pop(ctx);
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                    content: Text('Payment method removed'),
                    backgroundColor: AppColors.success),
              );
            },
            child:
                const Text('Remove', style: TextStyle(color: AppColors.error)),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('Payment Methods')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.push(AppRoutes.addPaymentMethod),
        backgroundColor: AppColors.primary,
        foregroundColor: AppColors.surface,
        icon: const Icon(LucideIcons.plus),
        label: const Text('Add Card'),
      ),
      body: _methods.isEmpty
          ? Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(LucideIcons.creditCard,
                      size: 64, color: AppColors.textTertiary),
                  const SizedBox(height: AppSpacing.md),
                  Text('No payment methods',
                      style: AppTypography.textTheme.titleLarge),
                  const SizedBox(height: AppSpacing.sm),
                  Text('Add a card to get started',
                      style: AppTypography.textTheme.bodyMedium),
                ],
              ),
            )
          : ListView.separated(
              padding: const EdgeInsets.all(AppSpacing.pagePadding),
              itemCount: _methods.length,
              separatorBuilder: (_, __) =>
                  const SizedBox(height: AppSpacing.md),
              itemBuilder: (context, index) {
                final method = _methods[index];
                return Container(
                  padding: const EdgeInsets.all(AppSpacing.md),
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
                    border: Border.all(
                      color: method.isDefault
                          ? AppColors.primary
                          : AppColors.border,
                      width: method.isDefault ? 1.5 : 1.0,
                    ),
                  ),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(AppSpacing.sm),
                        decoration: BoxDecoration(
                          color: AppColors.primaryLight,
                          borderRadius:
                              BorderRadius.circular(AppSpacing.radiusSm),
                        ),
                        child: Icon(_getCardIcon(method.type),
                            color: AppColors.primary),
                      ),
                      const SizedBox(width: AppSpacing.md),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(method.displayName,
                                style: AppTypography.textTheme.titleMedium),
                            if (method.expiryDate != null)
                              Text('Expires ${method.expiryDate}',
                                  style: AppTypography.textTheme.bodySmall),
                            if (method.isDefault)
                              Padding(
                                padding: const EdgeInsets.only(top: 4),
                                child: Container(
                                  padding: const EdgeInsets.symmetric(
                                      horizontal: 8, vertical: 2),
                                  decoration: BoxDecoration(
                                    color: AppColors.primary.withOpacity(0.1),
                                    borderRadius: BorderRadius.circular(4),
                                  ),
                                  child: Text('Default',
                                      style: AppTypography.textTheme.labelSmall
                                          ?.copyWith(color: AppColors.primary)),
                                ),
                              ),
                          ],
                        ),
                      ),
                      IconButton(
                        icon: const Icon(LucideIcons.trash2,
                            size: 20, color: AppColors.error),
                        onPressed: () => _deleteMethod(index),
                      ),
                    ],
                  ),
                );
              },
            ),
    );
  }
}
