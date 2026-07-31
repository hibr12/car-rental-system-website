import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../core/routes/app_routes.dart';
import '../../data/repositories/booking_repository.dart';
import '../../models/booking_draft.dart';
import '../../widgets/buttons/app_buttons.dart';

class BookingSummaryScreen extends StatefulWidget {
  final BookingDraft draft;

  const BookingSummaryScreen({super.key, required this.draft});

  @override
  State<BookingSummaryScreen> createState() => _BookingSummaryScreenState();
}

class _BookingSummaryScreenState extends State<BookingSummaryScreen> {
  bool _isLoading = false;
  bool _termsAccepted = false;

  // Pricing breakdown. The backend computes the authoritative total; these
  // are client-side estimates for display only.
  static const double _insurancePerDay = 25.0;
  static const double _serviceFee = 15.0;

  int get _days => widget.draft.numberOfDays;
  double get _rentalSubtotal => widget.draft.rentalSubtotal;
  double get _insurance => _insurancePerDay * _days;
  double get _estimatedTotal => _rentalSubtotal + _insurance + _serviceFee;

  Future<void> _handleConfirm() async {
    setState(() => _isLoading = true);

    final res = await BookingRepository.instance.createBooking(widget.draft);

    if (!mounted) return;
    setState(() => _isLoading = false);

    if (res.success && res.data != null) {
      // Navigate to success with the real booking (carries the reference).
      context.pushReplacement(AppRoutes.bookingSuccess, extra: res.data);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(res.error?.friendlyMessage ??
              'Failed to create booking. Please try again.'),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }

  String _formatDate(DateTime d) {
    const months = [
      'Jan',
      'Feb',
      'Mar',
      'Apr',
      'May',
      'Jun',
      'Jul',
      'Aug',
      'Sep',
      'Oct',
      'Nov',
      'Dec'
    ];
    return '${months[d.month - 1]} ${d.day}';
  }

  @override
  Widget build(BuildContext context) {
    final vehicle = widget.draft.vehicle;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('Checkout')),
      body: SafeArea(
        child: Column(
          children: [
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(AppSpacing.pagePadding),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _buildSectionHeader('Trip Summary'),
                    _buildSummaryCard(),
                    const SizedBox(height: AppSpacing.xxl),
                    _buildSectionHeader('Protection Plan'),
                    _buildProtectionCard(),
                    const SizedBox(height: AppSpacing.xxl),
                    _buildPolicies(),
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

  Widget _buildSectionHeader(String title) {
    return Padding(
      padding: const EdgeInsets.only(bottom: AppSpacing.md),
      child: Text(title, style: AppTypography.textTheme.headlineMedium),
    );
  }

  Widget _buildSummaryCard() {
    final vehicle = widget.draft.vehicle;
    return Container(
      padding: const EdgeInsets.all(AppSpacing.md),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        children: [
          Row(
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
                child: Image.network(
                  vehicle.imageUrls.first,
                  width: 80,
                  height: 60,
                  fit: BoxFit.cover,
                  errorBuilder: (_, __, ___) => Container(
                    width: 80,
                    height: 60,
                    color: AppColors.textTertiary,
                    child: const Icon(LucideIcons.imageOff, size: 20),
                  ),
                ),
              ),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(vehicle.fullName,
                        style: AppTypography.textTheme.titleLarge,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis),
                    const SizedBox(height: AppSpacing.xs),
                    Row(
                      children: [
                        const Icon(LucideIcons.calendar,
                            size: 14, color: AppColors.textTertiary),
                        const SizedBox(width: AppSpacing.xs),
                        Expanded(
                          child: Text(
                            '${_formatDate(widget.draft.pickupDate)} - '
                            '${_formatDate(widget.draft.returnDate)} '
                            '($_days day${_days == 1 ? '' : 's'})',
                            style: AppTypography.textTheme.bodySmall,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                    if (widget.draft.pickupLocation.isNotEmpty) ...[
                      const SizedBox(height: AppSpacing.xs),
                      Row(
                        children: [
                          const Icon(LucideIcons.mapPin,
                              size: 14, color: AppColors.textTertiary),
                          const SizedBox(width: AppSpacing.xs),
                          Expanded(
                            child: Text(
                              widget.draft.pickupLocation,
                              style: AppTypography.textTheme.bodySmall,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ],
                ),
              ),
            ],
          ),
          const Padding(
            padding: EdgeInsets.symmetric(vertical: AppSpacing.sm),
            child: Divider(),
          ),
          _buildPriceRow(
              '\$${vehicle.pricePerDay.toStringAsFixed(2)} x $_days day${_days == 1 ? '' : 's'}',
              _rentalSubtotal),
          const SizedBox(height: AppSpacing.sm),
          _buildPriceRow('Standard Insurance', _insurance),
          const SizedBox(height: AppSpacing.sm),
          _buildPriceRow('Service Fee', _serviceFee),
          const Padding(
            padding: EdgeInsets.symmetric(vertical: AppSpacing.sm),
            child: Divider(),
          ),
          _buildPriceRow('Estimated Total', _estimatedTotal, isBold: true),
          const SizedBox(height: AppSpacing.sm),
          Text(
            'Final total is confirmed by the server when the booking is created.',
            style: AppTypography.textTheme.bodySmall
                ?.copyWith(color: AppColors.textTertiary),
          ),
        ],
      ),
    );
  }

  Widget _buildPriceRow(String label, double amount, {bool isBold = false}) {
    final style = isBold
        ? AppTypography.textTheme.headlineMedium
            ?.copyWith(color: AppColors.textPrimary)
        : AppTypography.textTheme.bodyLarge;

    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Expanded(
          child: Text(label, style: style, overflow: TextOverflow.ellipsis),
        ),
        const SizedBox(width: AppSpacing.sm),
        Text('\$${amount.toStringAsFixed(2)}', style: style),
      ],
    );
  }

  Widget _buildProtectionCard() {
    return Container(
      padding: const EdgeInsets.all(AppSpacing.md),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
        border: Border.all(color: AppColors.primary.withOpacity(0.3)),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(AppSpacing.sm),
            decoration: BoxDecoration(
              color: AppColors.primaryLight,
              borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
            ),
            child:
                const Icon(LucideIcons.shieldCheck, color: AppColors.primary),
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Standard Protection',
                    style: AppTypography.textTheme.titleLarge),
                const SizedBox(height: AppSpacing.xs),
                Text('Includes physical damage coverage up to \$50k.',
                    style: AppTypography.textTheme.bodySmall),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPolicies() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('Cancellation Policy', style: AppTypography.textTheme.titleLarge),
        const SizedBox(height: AppSpacing.sm),
        Text(
          'Free cancellation up to 24 hours before the trip starts. '
          'A 50% fee applies for cancellations within 24 hours.',
          style: AppTypography.textTheme.bodyMedium,
        ),
        const SizedBox(height: AppSpacing.xxl),
        Row(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            Checkbox(
              value: _termsAccepted,
              activeColor: AppColors.primary,
              onChanged: (val) => setState(() => _termsAccepted = val ?? false),
            ),
            Expanded(
              child: Text(
                'I agree to the Rental Agreement and Cancellation Policy.',
                style: AppTypography.textTheme.bodyMedium,
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildBottomBar() {
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
      child: PrimaryButton(
        text: 'Confirm Booking',
        isLoading: _isLoading,
        onPressed: _termsAccepted ? _handleConfirm : null,
      ),
    );
  }
}
