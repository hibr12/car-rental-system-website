import 'package:flutter/material.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';

class FilterSection extends StatelessWidget {
  final String title;
  final Widget child;

  const FilterSection({
    super.key,
    required this.title,
    required this.child,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(title, style: AppTypography.textTheme.titleMedium),
        const SizedBox(height: AppSpacing.md),
        child,
        const SizedBox(height: AppSpacing.xxl),
      ],
    );
  }
}

class FilterChoiceChip extends StatelessWidget {
  final String label;
  final bool isSelected;
  final VoidCallback onTap;

  const FilterChoiceChip({
    super.key,
    required this.label,
    required this.isSelected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
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

class FilterChoiceChipRow extends StatelessWidget {
  final List<String> options;
  final String selectedOption;
  final ValueChanged<String> onSelected;

  const FilterChoiceChipRow({
    super.key,
    required this.options,
    required this.selectedOption,
    required this.onSelected,
  });

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: options.map((option) {
          return Padding(
            padding: const EdgeInsets.only(right: AppSpacing.sm),
            child: FilterChoiceChip(
              label: option,
              isSelected: selectedOption == option,
              onTap: () => onSelected(option),
            ),
          );
        }).toList(),
      ),
    );
  }
}

class FilterRangeSlider extends StatelessWidget {
  final double min;
  final double max;
  final RangeValues values;
  final ValueChanged<RangeValues> onChanged;
  final String prefix;

  const FilterRangeSlider({
    super.key,
    required this.min,
    required this.max,
    required this.values,
    required this.onChanged,
    this.prefix = '\$',
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        RangeSlider(
          values: values,
          min: min,
          max: max,
          activeColor: AppColors.primary,
          inactiveColor: AppColors.border,
          labels: RangeLabels(
              '$prefix${values.start.toInt()}', '$prefix${values.end.toInt()}'),
          onChanged: onChanged,
        ),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text('$prefix${values.start.toInt()}',
                style: AppTypography.textTheme.bodyMedium),
            Text('$prefix${values.end.toInt()}',
                style: AppTypography.textTheme.bodyMedium),
          ],
        ),
      ],
    );
  }
}
