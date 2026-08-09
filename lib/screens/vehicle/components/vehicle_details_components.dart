import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:go_router/go_router.dart';
import '../../../core/colors/app_colors.dart';
import '../../../core/spacing/app_spacing.dart';
import '../../../core/typography/app_typography.dart';
import '../../../models/vehicle_model.dart';
import '../../../widgets/buttons/app_buttons.dart';
import '../../../core/routes/app_routes.dart';

class VehicleTitleSection extends StatelessWidget {
  final Vehicle vehicle;

  const VehicleTitleSection({super.key, required this.vehicle});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Expanded(
              child: Text(
                vehicle.fullName,
                style: AppTypography.textTheme.displaySmall,
              ),
            ),
            InkWell(
              onTap: () {
                context.push(AppRoutes.reviews, extra: vehicle);
              },
              borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
              child: Padding(
                padding:
                    const EdgeInsets.symmetric(horizontal: 4.0, vertical: 2.0),
                child: Row(
                  children: [
                    const Icon(LucideIcons.star,
                        color: AppColors.warning, size: 20),
                    const SizedBox(width: AppSpacing.xs),
                    Text(
                      vehicle.rating.toString(),
                      style: AppTypography.textTheme.titleLarge,
                    ),
                    Text(
                      ' (${vehicle.reviewCount})',
                      style: AppTypography.textTheme.bodyMedium,
                    ),
                    const SizedBox(width: AppSpacing.xs),
                    const Icon(LucideIcons.chevronRight,
                        size: 16, color: AppColors.textTertiary),
                  ],
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: AppSpacing.sm),
        Row(
          children: [
            const Icon(LucideIcons.mapPin,
                size: 16, color: AppColors.textTertiary),
            const SizedBox(width: AppSpacing.xs),
            Text(
              vehicle.location,
              style: AppTypography.textTheme.bodyLarge,
            ),
          ],
        ),
      ],
    );
  }
}

class VehicleSpecificationsSection extends StatelessWidget {
  final Vehicle vehicle;

  const VehicleSpecificationsSection({super.key, required this.vehicle});

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: [
          _buildSpecItem(LucideIcons.gauge, '${vehicle.seats} Seats'),
          const SizedBox(width: AppSpacing.sm),
          _buildSpecItem(LucideIcons.fuel, vehicle.fuelType),
          const SizedBox(width: AppSpacing.sm),
          _buildSpecItem(LucideIcons.settings, vehicle.transmission),
          const SizedBox(width: AppSpacing.sm),
          _buildSpecItem(LucideIcons.palette, vehicle.color),
        ],
      ),
    );
  }

  Widget _buildSpecItem(IconData icon, String label) {
    return Container(
      width: 70,
      padding: const EdgeInsets.all(AppSpacing.sm),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        children: [
          Icon(icon, color: AppColors.primary),
          const SizedBox(height: AppSpacing.sm),
          Text(
            label,
            style: AppTypography.textTheme.labelSmall,
            textAlign: TextAlign.center,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }
}

class VehicleDescriptionSection extends StatelessWidget {
  final Vehicle vehicle;

  const VehicleDescriptionSection({super.key, required this.vehicle});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('Description', style: AppTypography.textTheme.headlineMedium),
        const SizedBox(height: AppSpacing.sm),
        Text(
          vehicle.description,
          style: AppTypography.textTheme.bodyLarge,
        ),
      ],
    );
  }
}

class VehicleFeaturesSection extends StatelessWidget {
  final Vehicle vehicle;

  const VehicleFeaturesSection({super.key, required this.vehicle});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('Features', style: AppTypography.textTheme.headlineMedium),
        const SizedBox(height: AppSpacing.sm),
        Wrap(
          spacing: AppSpacing.sm,
          runSpacing: AppSpacing.sm,
          children: vehicle.features.map((feature) {
            return Chip(
              label: Text(feature),
              backgroundColor: AppColors.surface,
              side: const BorderSide(color: AppColors.border),
            );
          }).toList(),
        ),
      ],
    );
  }
}

class VehicleBottomBar extends StatelessWidget {
  final Vehicle vehicle;

  const VehicleBottomBar({super.key, required this.vehicle});

  @override
  Widget build(BuildContext context) {
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
      child: SafeArea(
        child: Row(
          children: [
            Expanded(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Total Price',
                      style: AppTypography.textTheme.bodyMedium),
                  RichText(
                    text: TextSpan(
                      children: [
                        TextSpan(
                          text: '\$${vehicle.pricePerDay.toInt()}',
                          style: AppTypography.textTheme.displaySmall
                              ?.copyWith(color: AppColors.primary),
                        ),
                        TextSpan(
                          text: ' /day',
                          style: AppTypography.textTheme.bodyLarge,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            Expanded(
              child: PrimaryButton(
                text: 'Book Now',
                onPressed: () {
                  context.push(AppRoutes.bookingDate, extra: vehicle);
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}
