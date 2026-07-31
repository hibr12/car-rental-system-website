import 'package:flutter/material.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../models/category_model.dart';
import '../buttons/app_buttons.dart';

/// A bottom sheet for refining the vehicle list.
///
/// Returns a [VehicleFilter] via [Navigator.pop] when the user taps
/// "Apply Filters", or `null` when dismissed without applying.
class FilterBottomSheet extends StatefulWidget {
  /// The currently applied filter, used to pre-fill the controls.
  final VehicleFilter current;

  const FilterBottomSheet({
    super.key,
    this.current = VehicleFilter.empty,
  });

  @override
  State<FilterBottomSheet> createState() => _FilterBottomSheetState();
}

class _FilterBottomSheetState extends State<FilterBottomSheet> {
  late double _minPrice;
  late double _maxPrice;
  late String _transmission;
  late int _minSeats;

  static const double _absoluteMin = 20;
  static const double _absoluteMax = 1000;

  @override
  void initState() {
    super.initState();
    _minPrice = widget.current.minPrice ?? 50;
    _maxPrice = widget.current.maxPrice ?? 300;
    _transmission = widget.current.transmission ?? 'Any';
    _minSeats = widget.current.minSeats ?? 4;
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(AppSpacing.pagePadding),
      decoration: const BoxDecoration(
        color: AppColors.background,
        borderRadius:
            BorderRadius.vertical(top: Radius.circular(AppSpacing.radiusXl)),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Center(
            child: Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: AppColors.border,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          ),
          const SizedBox(height: AppSpacing.lg),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('Filters', style: AppTypography.textTheme.headlineMedium),
              TextButton(
                onPressed: _reset,
                child: const Text('Reset'),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.md),

          // ── Price range ─────────────────────────────────────────────
          Text('Price Range (per day)',
              style: AppTypography.textTheme.titleMedium),
          const SizedBox(height: AppSpacing.sm),
          RangeSlider(
            values: RangeValues(_minPrice, _maxPrice),
            min: _absoluteMin,
            max: _absoluteMax,
            divisions: 49, // step of ~20
            activeColor: AppColors.primary,
            inactiveColor: AppColors.border,
            labels:
                RangeLabels('\$${_minPrice.toInt()}', '\$${_maxPrice.toInt()}'),
            onChanged: (values) {
              setState(() {
                _minPrice = values.start;
                _maxPrice = values.end;
              });
            },
          ),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('\$${_minPrice.toInt()}',
                  style: AppTypography.textTheme.bodyMedium),
              Text('\$${_maxPrice.toInt()}',
                  style: AppTypography.textTheme.bodyMedium),
            ],
          ),

          const SizedBox(height: AppSpacing.xxl),

          // ── Transmission ────────────────────────────────────────────
          Text('Transmission', style: AppTypography.textTheme.titleMedium),
          const SizedBox(height: AppSpacing.md),
          Wrap(
            spacing: AppSpacing.sm,
            runSpacing: AppSpacing.sm,
            children: [
              _chip('Any', _transmission == 'Any',
                  () => setState(() => _transmission = 'Any')),
              _chip('Automatic', _transmission == 'Automatic',
                  () => setState(() => _transmission = 'Automatic')),
              _chip('Manual', _transmission == 'Manual',
                  () => setState(() => _transmission = 'Manual')),
            ],
          ),

          const SizedBox(height: AppSpacing.xxl),

          // ── Minimum seats ───────────────────────────────────────────
          Text('Minimum Seats', style: AppTypography.textTheme.titleMedium),
          const SizedBox(height: AppSpacing.md),
          Wrap(
            spacing: AppSpacing.sm,
            runSpacing: AppSpacing.sm,
            children: [
              _chip('Any', _minSeats == 0, () => setState(() => _minSeats = 0)),
              _chip('2+', _minSeats == 2, () => setState(() => _minSeats = 2)),
              _chip('4+', _minSeats == 4, () => setState(() => _minSeats = 4)),
              _chip('5+', _minSeats == 5, () => setState(() => _minSeats = 5)),
              _chip('7+', _minSeats == 7, () => setState(() => _minSeats = 7)),
            ],
          ),

          const SizedBox(height: AppSpacing.xxxl),
          PrimaryButton(
            text: 'Apply Filters',
            onPressed: () {
              final result = VehicleFilter(
                minPrice: _minPrice,
                maxPrice: _maxPrice,
                transmission: _transmission,
                minSeats: _minSeats,
              );
              Navigator.pop(context, result);
            },
          ),
          const SizedBox(height: AppSpacing.md), // SafeArea bottom pad
        ],
      ),
    );
  }

  void _reset() {
    setState(() {
      _minPrice = 50;
      _maxPrice = 300;
      _transmission = 'Any';
      _minSeats = 4;
    });
  }

  Widget _chip(String label, bool isSelected, VoidCallback onTap) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(AppSpacing.radiusPill),
      child: Container(
        padding: const EdgeInsets.symmetric(
            horizontal: AppSpacing.lg, vertical: AppSpacing.sm),
        decoration: BoxDecoration(
          color: isSelected ? AppColors.primary : AppColors.surface,
          borderRadius: BorderRadius.circular(AppSpacing.radiusPill),
          border: Border.all(
              color: isSelected ? AppColors.primary : AppColors.border),
        ),
        child: Text(
          label,
          style: AppTypography.textTheme.bodyMedium?.copyWith(
            color: isSelected ? AppColors.surface : AppColors.textPrimary,
          ),
        ),
      ),
    );
  }
}
