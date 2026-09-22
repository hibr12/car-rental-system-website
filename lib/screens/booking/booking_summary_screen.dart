import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../core/routes/app_routes.dart';
import '../../core/utils/formatters.dart';
import '../../data/repositories/booking_repository.dart';
import '../../data/repositories/driver_license_repository.dart';
import '../../models/booking_draft.dart';
import '../../models/price_estimate_model.dart';
import '../../widgets/buttons/app_buttons.dart';

class BookingSummaryScreen extends StatefulWidget {
  final BookingDraft draft;

  const BookingSummaryScreen({super.key, required this.draft});

  @override
  State<BookingSummaryScreen> createState() => _BookingSummaryScreenState();
}

class _BookingSummaryScreenState extends State<BookingSummaryScreen> {
  bool _isLoading = true;
  bool _isSubmitting = false;
  bool _termsAccepted = false;
  String? _error;

  // Server-side values — the backend is the authority.
  PriceEstimate? _estimate;
  bool _isAvailable = true;

  /// Driver-license eligibility from `GET /customer/license/eligibility`.
  bool _licenseEligible = true;
  String? _licenseBlocker;
  bool _canOpenLicenseScreen = false;

  @override
  void initState() {
    super.initState();
    _loadPricingAndAvailability();
  }

  Future<void> _loadPricingAndAvailability() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    // Run availability check and price estimate in parallel.
    final results = await Future.wait([
      BookingRepository.instance.checkAvailability(
        vehicleId: widget.draft.vehicle.id,
        pickupDate: widget.draft.pickupDate,
        returnDate: widget.draft.returnDate,
      ),
      BookingRepository.instance.getPriceEstimate(
        vehicleId: widget.draft.vehicle.id,
        pickupDate: widget.draft.pickupDate,
        returnDate: widget.draft.returnDate,
      ),
    ]);

    if (!mounted) return;

    final availRes = results[0];
    final priceRes = results[1];

    if (!availRes.success) {
      setState(() {
        _isLoading = false;
        _error = availRes.error?.friendlyMessage ??
            'Could not check vehicle availability.';
      });
      return;
    }

    final available = availRes.data as bool? ?? false;
    if (!available) {
      setState(() {
        _isLoading = false;
        _isAvailable = false;
        _error = 'This vehicle is not available for your selected dates. '
            'Please go back and choose different dates.';
      });
      return;
    }

    if (!priceRes.success) {
      setState(() {
        _isLoading = false;
        _error = priceRes.error?.friendlyMessage ??
            'Could not get pricing information.';
      });
      return;
    }

    setState(() {
      _isLoading = false;
      _isAvailable = true;
      _estimate = priceRes.data as PriceEstimate?;
    });

    _checkLicenseEligibility();
  }

  /// Fails fast on license blockers so customers don't discover them only
  /// after hitting Confirm (the backend enforces the same rule again).
  Future<void> _checkLicenseEligibility() async {
    final res = await DriverLicenseRepository.instance.checkEligibility(
      vehicleId: int.tryParse(widget.draft.vehicle.id),
    );
    if (!mounted) return;

    final data = res.data?['data'];
    if (res.success && data is Map<String, dynamic>) {
      final eligible = data['eligible'] as bool? ?? true;
      final reason = data['reason'] as String?;
      final code = data['code'] as String? ?? '';
      setState(() {
        _licenseEligible = eligible;
        _licenseBlocker = eligible
            ? null
            : (reason ?? 'Your driver license is not verified for this vehicle.');
        _canOpenLicenseScreen = const [
          'LICENSE_NOT_SUBMITTED',
          'LICENSE_REJECTED',
          'LICENSE_EXPIRED',
        ].contains(code);
      });
    }
    // If the check itself fails we stay silent: the backend re-validates at
    // booking time and its error surfaces then.
  }

  Future<void> _handleConfirm() async {
    setState(() => _isSubmitting = true);

    final res = await BookingRepository.instance.createBooking(widget.draft);

    if (!mounted) return;
    setState(() => _isSubmitting = false);

    if (res.success && res.data != null) {
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

  String _formatDate(DateTime d) => Formatters.date(d);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('Checkout')),
      body: SafeArea(
        child: _isLoading
            ? const Center(child: CircularProgressIndicator())
            : _error != null && !_isAvailable
                ? _buildUnavailable()
                : Column(
                    children: [
                      Expanded(
                        child: SingleChildScrollView(
                          padding:
                              const EdgeInsets.all(AppSpacing.pagePadding),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              if (_error != null && _isAvailable)
                                _buildErrorBanner(),
                              _buildSectionHeader('Trip Summary'),
                              _buildSummaryCard(),
                              const SizedBox(height: AppSpacing.xxl),
                              if (_estimate != null) ...[
                                _buildSectionHeader('Price Breakdown'),
                                _buildPricingCard(),
                                const SizedBox(height: AppSpacing.xxl),
                              ],
                              _buildLicenseStatus(),
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

  Widget _buildUnavailable() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.pagePadding),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(AppSpacing.xxl),
              decoration: BoxDecoration(
                color: AppColors.warning.withOpacity(0.1),
                shape: BoxShape.circle,
              ),
              child: const Icon(LucideIcons.calendarX,
                  size: 64, color: AppColors.warning),
            ),
            const SizedBox(height: AppSpacing.xxl),
            Text(
              'Vehicle Unavailable',
              style: AppTypography.textTheme.headlineMedium,
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: AppSpacing.md),
            Text(
              _error!,
              style: AppTypography.textTheme.bodyLarge
                  ?.copyWith(color: AppColors.textSecondary),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: AppSpacing.xxl),
            PrimaryButton(
              text: 'Choose Different Dates',
              onPressed: () => context.pop(),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildErrorBanner() {
    return Container(
      margin: const EdgeInsets.only(bottom: AppSpacing.lg),
      padding: const EdgeInsets.all(AppSpacing.md),
      decoration: BoxDecoration(
        color: AppColors.warning.withOpacity(0.1),
        borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
      ),
      child: Row(
        children: [
          const Icon(LucideIcons.alertTriangle,
              size: 16, color: AppColors.warning),
          const SizedBox(width: AppSpacing.sm),
          Expanded(
            child: Text(
              _error!,
              style: AppTypography.textTheme.bodySmall
                  ?.copyWith(color: AppColors.warning),
            ),
          ),
          IconButton(
            icon: const Icon(LucideIcons.refreshCw, size: 16),
            onPressed: _loadPricingAndAvailability,
          ),
        ],
      ),
    );
  }

  Widget _buildSectionHeader(String title) {
    return Padding(
      padding: const EdgeInsets.only(bottom: AppSpacing.md),
      child: Text(title, style: AppTypography.textTheme.headlineMedium),
    );
  }

  /// Shown only when the eligibility endpoint reports a blocker.
  Widget _buildLicenseStatus() {
    if (_licenseEligible) return const SizedBox.shrink();

    return Container(
      padding: const EdgeInsets.all(AppSpacing.md),
      decoration: BoxDecoration(
        color: AppColors.error.withOpacity(0.06),
        borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
        border: Border.all(color: AppColors.error.withOpacity(0.3)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(LucideIcons.alertCircle,
                  size: 18, color: AppColors.error),
              const SizedBox(width: AppSpacing.sm),
              Expanded(
                child: Text('Driver license check',
                    style: AppTypography.textTheme.titleMedium
                        ?.copyWith(color: AppColors.error)),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.sm),
          Text(_licenseBlocker ?? '',
              style: AppTypography.textTheme.bodyMedium
                  ?.copyWith(color: AppColors.textSecondary)),
          if (_canOpenLicenseScreen) ...[
            const SizedBox(height: AppSpacing.sm),
            Align(
              alignment: Alignment.centerRight,
              child: TextButton.icon(
                onPressed: () async {
                  await context.push(AppRoutes.driverLicense);
                  if (mounted) _checkLicenseEligibility();
                },
                icon: const Icon(LucideIcons.upload, size: 16),
                label: const Text('Upload License'),
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildSummaryCard() {
    final vehicle = widget.draft.vehicle;
    final days = _estimate?.numberOfDays ?? widget.draft.numberOfDays;

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
                        '($days day${days == 1 ? '' : 's'})',
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
    );
  }

  Widget _buildPricingCard() {
    final est = _estimate!;
    return Container(
      padding: const EdgeInsets.all(AppSpacing.md),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        children: [
          _buildPriceRow(
            '${Formatters.etb(est.pricePerDay)} × ${est.numberOfDays} day${est.numberOfDays == 1 ? '' : 's'}',
            est.subtotal,
          ),
          const SizedBox(height: AppSpacing.sm),
          if (est.additionalCharges > 0) ...[
            _buildPriceRow('Additional Charges', est.additionalCharges),
            const SizedBox(height: AppSpacing.sm),
          ],
          if (est.discount > 0) ...[
            _buildPriceRow('Discount', -est.discount,
                valueColor: AppColors.success),
            const SizedBox(height: AppSpacing.sm),
          ],
          const Padding(
            padding: EdgeInsets.symmetric(vertical: AppSpacing.sm),
            child: Divider(),
          ),
          _buildPriceRow('Total', est.totalPrice, isBold: true),
        ],
      ),
    );
  }

  Widget _buildPriceRow(String label, double amount,
      {bool isBold = false, Color? valueColor}) {
    final style = isBold
        ? AppTypography.textTheme.headlineMedium
            ?.copyWith(color: AppColors.textPrimary)
        : AppTypography.textTheme.bodyLarge;

    final text = amount < 0
        ? '-${Formatters.etb(amount.abs())}'
        : Formatters.etb(amount);

    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Expanded(
          child: Text(label, style: style, overflow: TextOverflow.ellipsis),
        ),
        const SizedBox(width: AppSpacing.sm),
        Text(text, style: style?.copyWith(color: valueColor)),
      ],
    );
  }

  Widget _buildPolicies() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('Cancellation Policy', style: AppTypography.textTheme.titleLarge),
        const SizedBox(height: AppSpacing.sm),
        Text(
          'You can cancel a booking while it is awaiting payment or approval. '
          'Refunds of completed payments are reviewed and processed by our '
          'team — contact support after cancelling.',
          style: AppTypography.textTheme.bodyMedium,
        ),
        const SizedBox(height: AppSpacing.xxl),
        Row(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            Checkbox(
              value: _termsAccepted,
              activeColor: AppColors.primary,
              onChanged: (val) =>
                  setState(() => _termsAccepted = val ?? false),
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
    final total = _estimate?.totalPrice;
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
          if (total != null)
            Padding(
              padding: const EdgeInsets.only(bottom: AppSpacing.sm),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('Total',
                      style: AppTypography.textTheme.titleLarge),
                  Text(Formatters.etb(total),
                      style: AppTypography.textTheme.headlineMedium
                          ?.copyWith(color: AppColors.primary)),
                ],
              ),
            ),
          PrimaryButton(
            text: _licenseEligible ? 'Confirm Booking' : 'License Required',
            isLoading: _isSubmitting,
            onPressed: _termsAccepted &&
                    _isAvailable &&
                    _estimate != null &&
                    _licenseEligible
                ? _handleConfirm
                : null,
          ),
        ],
      ),
    );
  }
}
