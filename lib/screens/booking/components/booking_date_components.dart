import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:intl/intl.dart';
import '../../../core/colors/app_colors.dart';
import '../../../core/spacing/app_spacing.dart';
import '../../../core/typography/app_typography.dart';
import '../../../models/vehicle_model.dart';

class VehicleSummaryCard extends StatelessWidget {
  final Vehicle vehicle;

  const VehicleSummaryCard({super.key, required this.vehicle});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(AppSpacing.md),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
            child: Image.network(
              vehicle.imageUrls.first,
              width: 80,
              height: 60,
              fit: BoxFit.cover,
            ),
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(vehicle.fullName,
                    style: AppTypography.textTheme.titleLarge),
                const SizedBox(height: AppSpacing.xs),
                Text('\$${vehicle.pricePerDay.toInt()} / day',
                    style: AppTypography.textTheme.bodyMedium
                        ?.copyWith(color: AppColors.primary)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class DateSelectorCard extends StatelessWidget {
  final DateTime? startDate;
  final DateTime? endDate;
  final VoidCallback onTap;

  const DateSelectorCard({
    super.key,
    required this.startDate,
    required this.endDate,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final DateFormat formatter = DateFormat('EEE, MMM d, yyyy');

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
      child: Container(
        padding: const EdgeInsets.all(AppSpacing.md),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
          border: Border.all(color: AppColors.border),
        ),
        child: Row(
          children: [
            const Icon(LucideIcons.calendar, color: AppColors.primary),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    startDate == null || endDate == null
                        ? 'Select Dates'
                        : '${formatter.format(startDate!)} - ${formatter.format(endDate!)}',
                    style: AppTypography.textTheme.titleLarge,
                  ),
                  if (startDate != null && endDate != null) ...[
                    const SizedBox(height: AppSpacing.xs),
                    Text('${endDate!.difference(startDate!).inDays} days total',
                        style: AppTypography.textTheme.bodySmall),
                  ]
                ],
              ),
            ),
            const Icon(LucideIcons.chevronRight, color: AppColors.textTertiary),
          ],
        ),
      ),
    );
  }
}

class TimeSelectorCard extends StatelessWidget {
  final TimeOfDay? time;
  final bool isStart;
  final VoidCallback onTap;

  const TimeSelectorCard({
    super.key,
    required this.time,
    required this.isStart,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
      child: Container(
        padding: const EdgeInsets.all(AppSpacing.md),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
          border: Border.all(color: AppColors.border),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(isStart ? 'Pickup Time' : 'Return Time',
                style: AppTypography.textTheme.bodySmall),
            const SizedBox(height: AppSpacing.xs),
            Row(
              children: [
                const Icon(LucideIcons.clock,
                    size: 16, color: AppColors.textSecondary),
                const SizedBox(width: AppSpacing.sm),
                Text(time?.format(context) ?? '10:00 AM',
                    style: AppTypography.textTheme.titleMedium),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class LocationInfoCard extends StatelessWidget {
  final String location;

  const LocationInfoCard({super.key, required this.location});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('Pickup & Return Location',
            style: AppTypography.textTheme.headlineMedium),
        const SizedBox(height: AppSpacing.md),
        Container(
          padding: const EdgeInsets.all(AppSpacing.md),
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
            border: Border.all(color: AppColors.border),
          ),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(AppSpacing.sm),
                decoration: BoxDecoration(
                  color: AppColors.primaryLight,
                  borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
                ),
                child: const Icon(LucideIcons.mapPin, color: AppColors.primary),
              ),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(location, style: AppTypography.textTheme.titleLarge),
                    const SizedBox(height: AppSpacing.xs),
                    Text('Exact address provided after booking',
                        style: AppTypography.textTheme.bodySmall),
                  ],
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
