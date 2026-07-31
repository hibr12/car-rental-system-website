import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../core/routes/app_routes.dart';
import '../../models/booking_draft.dart';
import '../../models/vehicle_model.dart';
import '../../widgets/buttons/app_buttons.dart';

class ExtrasScreen extends StatefulWidget {
  final Vehicle vehicle;
  final BookingDraft? draft;
  const ExtrasScreen({super.key, required this.vehicle, this.draft});

  @override
  State<ExtrasScreen> createState() => _ExtrasScreenState();
}

class _ExtrasScreenState extends State<ExtrasScreen> {
  final Map<String, bool> _selectedExtras = {
    'child_seat': false,
    'gps': false,
    'prepaid_fuel': false,
    'additional_driver': false,
  };

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('Add Extras')),
      body: SafeArea(
        child: Column(
          children: [
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(AppSpacing.pagePadding),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Enhance Your Trip',
                        style: AppTypography.textTheme.displaySmall),
                    const SizedBox(height: AppSpacing.md),
                    Text(
                      'Add optional extras to make your journey more comfortable.',
                      style: AppTypography.textTheme.bodyLarge,
                    ),
                    const SizedBox(height: AppSpacing.xxl),
                    _buildExtraItem(
                      id: 'child_seat',
                      icon: LucideIcons.baby,
                      title: 'Child Seat',
                      description: 'Suitable for toddlers 20-40 lbs.',
                      price: 15.0,
                    ),
                    _buildExtraItem(
                      id: 'gps',
                      icon: LucideIcons.map,
                      title: 'GPS Navigation',
                      description: 'Never get lost with built-in navigation.',
                      price: 10.0,
                    ),
                    _buildExtraItem(
                      id: 'prepaid_fuel',
                      icon: LucideIcons.fuel,
                      title: 'Prepaid Fuel',
                      description:
                          'Return the car at any fuel level. Price based on current market rates.',
                      price: 65.0,
                    ),
                    _buildExtraItem(
                      id: 'additional_driver',
                      icon: LucideIcons.users,
                      title: 'Additional Driver',
                      description: 'Share the driving responsibilities safely.',
                      price: 20.0,
                    ),
                  ],
                ),
              ),
            ),
            _buildBottomBar(),
          ],
        ),
      ),
    );
  }

  Widget _buildExtraItem({
    required String id,
    required IconData icon,
    required String title,
    required String description,
    required double price,
  }) {
    final isSelected = _selectedExtras[id] ?? false;

    return Container(
      margin: const EdgeInsets.only(bottom: AppSpacing.md),
      padding: const EdgeInsets.all(AppSpacing.md),
      decoration: BoxDecoration(
        color: isSelected ? AppColors.primaryLight : AppColors.surface,
        borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
        border: Border.all(
            color: isSelected ? AppColors.primary : AppColors.border,
            width: isSelected ? 1.5 : 1.0),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(AppSpacing.sm),
            decoration: BoxDecoration(
              color: isSelected ? AppColors.primary : AppColors.background,
              borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
            ),
            child: Icon(icon,
                color: isSelected ? AppColors.surface : AppColors.primary),
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: AppTypography.textTheme.titleMedium),
                const SizedBox(height: AppSpacing.xs),
                Text(description, style: AppTypography.textTheme.bodyMedium),
                const SizedBox(height: AppSpacing.sm),
                Text('+\$${price.toStringAsFixed(2)} / trip',
                    style: AppTypography.textTheme.titleSmall
                        ?.copyWith(color: AppColors.textSecondary)),
              ],
            ),
          ),
          Checkbox(
            value: isSelected,
            activeColor: AppColors.primary,
            onChanged: (val) {
              setState(() {
                _selectedExtras[id] = val ?? false;
              });
            },
          ),
        ],
      ),
    );
  }

  Widget _buildBottomBar() {
    double totalExtras = 0;
    if (_selectedExtras['child_seat']!) totalExtras += 15.0;
    if (_selectedExtras['gps']!) totalExtras += 10.0;
    if (_selectedExtras['prepaid_fuel']!) totalExtras += 65.0;
    if (_selectedExtras['additional_driver']!) totalExtras += 20.0;

    return Container(
      padding: const EdgeInsets.all(AppSpacing.pagePadding),
      decoration: BoxDecoration(
        color: AppColors.surface,
        boxShadow: [
          BoxShadow(
            color: AppColors.textPrimary.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, -4),
          ),
        ],
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (totalExtras > 0) ...[
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text('Extras Total',
                    style: AppTypography.textTheme.titleMedium),
                Text('\$${totalExtras.toStringAsFixed(2)}',
                    style: AppTypography.textTheme.headlineMedium),
              ],
            ),
            const SizedBox(height: AppSpacing.md),
          ],
          PrimaryButton(
            text: 'Continue to Checkout',
            onPressed: () {
              // Forward the draft (if present) to the summary screen.
              context.push(AppRoutes.bookingSummary,
                  extra: widget.draft ?? widget.vehicle);
            },
          ),
        ],
      ),
    );
  }
}
