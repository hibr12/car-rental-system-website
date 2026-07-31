import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:intl/intl.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../core/routes/app_routes.dart';
import '../../models/booking_model.dart';
import '../../data/repositories/payment_repository.dart';
import '../../widgets/buttons/app_buttons.dart';
import '../../widgets/states/status_badge.dart';
import 'package:go_router/go_router.dart';

class ReservationDetailsScreen extends StatefulWidget {
  final Booking booking;

  const ReservationDetailsScreen({super.key, required this.booking});

  @override
  State<ReservationDetailsScreen> createState() =>
      _ReservationDetailsScreenState();
}

class _ReservationDetailsScreenState extends State<ReservationDetailsScreen> {
  bool _isPaymentLoading = false;

  Booking get booking => widget.booking;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Reservation Details'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(AppSpacing.pagePadding),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildStatusHeader(),
            const SizedBox(height: AppSpacing.xxl),
            _buildVehicleInfo(),
            const SizedBox(height: AppSpacing.xxl),
            _buildPaymentInfo(),
            const SizedBox(height: AppSpacing.xxl),
            _buildTimeline(),
            const SizedBox(height: AppSpacing.xxl),
            _buildActionButtons(context),
          ],
        ),
      ),
    );
  }

  Widget _buildStatusHeader() {
    BadgeStatus badgeStatus;
    switch (booking.status) {
      case BookingStatus.pending:
      case BookingStatus.confirmed:
        badgeStatus = BadgeStatus.pending;
        break;
      case BookingStatus.active:
        badgeStatus = BadgeStatus.active;
        break;
      case BookingStatus.completed:
        badgeStatus = BadgeStatus.completed;
        break;
      case BookingStatus.cancelled:
      case BookingStatus.rejected:
        badgeStatus = BadgeStatus.cancelled;
        break;
    }

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(AppSpacing.lg),
      decoration: BoxDecoration(
        color: AppColors.primaryLight,
        borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
      ),
      child: Column(
        children: [
          StatusBadge(label: booking.statusLabel, status: badgeStatus),
          const SizedBox(height: AppSpacing.md),
          Text('Booking Reference',
              style: AppTypography.textTheme.bodyMedium
                  ?.copyWith(color: AppColors.primary)),
          const SizedBox(height: AppSpacing.xs),
          Text(
            booking.bookingReference.isNotEmpty
                ? booking.bookingReference
                : 'N/A',
            style: AppTypography.textTheme.headlineMedium
                ?.copyWith(color: AppColors.primary),
          ),
        ],
      ),
    );
  }

  Widget _buildVehicleInfo() {
    return Row(
      children: [
        ClipRRect(
          borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
          child: Image.network(
            booking.vehicle.imageUrls.first,
            width: 100,
            height: 70,
            fit: BoxFit.cover,
            errorBuilder: (_, __, ___) => Container(
              width: 100,
              height: 70,
              color: AppColors.textTertiary,
              child: const Icon(LucideIcons.imageOff, size: 24),
            ),
          ),
        ),
        const SizedBox(width: AppSpacing.md),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(booking.vehicle.fullName,
                  style: AppTypography.textTheme.titleLarge,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis),
              const SizedBox(height: AppSpacing.xs),
              Row(
                children: [
                  const Icon(LucideIcons.mapPin,
                      size: 14, color: AppColors.textTertiary),
                  const SizedBox(width: AppSpacing.xs),
                  Expanded(
                    child: Text(
                      booking.pickupLocation.isNotEmpty
                          ? booking.pickupLocation
                          : 'Location not set',
                      style: AppTypography.textTheme.bodyMedium,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildPaymentInfo() {
    return Container(
      padding: const EdgeInsets.all(AppSpacing.md),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Payment Details',
              style: AppTypography.textTheme.headlineMedium),
          const SizedBox(height: AppSpacing.md),
          _buildDetailRow(
              'Total Amount', '\$${booking.totalAmount.toStringAsFixed(2)}'),
          const SizedBox(height: AppSpacing.sm),
          _buildDetailRow('Payment Status', booking.paymentStatus.label),
          if (booking.numberOfDays > 0) ...[
            const SizedBox(height: AppSpacing.sm),
            _buildDetailRow('Duration', '${booking.numberOfDays} day(s)'),
          ],
          if (booking.pricePerDay > 0) ...[
            const SizedBox(height: AppSpacing.sm),
            _buildDetailRow(
                'Daily Rate', '\$${booking.pricePerDay.toStringAsFixed(2)}'),
          ],
          if (booking.notes.isNotEmpty) ...[
            const SizedBox(height: AppSpacing.sm),
            _buildDetailRow('Notes', booking.notes),
          ],
        ],
      ),
    );
  }

  Widget _buildDetailRow(String label, String value) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: AppTypography.textTheme.bodyMedium),
        Flexible(
          child: Text(
            value,
            style: AppTypography.textTheme.titleMedium,
            textAlign: TextAlign.end,
            overflow: TextOverflow.ellipsis,
          ),
        ),
      ],
    );
  }

  Widget _buildTimeline() {
    final dateFormat = DateFormat('MMM d, yyyy');

    final bool pickupDone = booking.status == BookingStatus.active ||
        booking.status == BookingStatus.completed;
    final bool tripActive = booking.status == BookingStatus.active;
    final bool tripCompleted = booking.status == BookingStatus.completed;
    final bool isCancelled = booking.status == BookingStatus.cancelled ||
        booking.status == BookingStatus.rejected;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('Rental Timeline', style: AppTypography.textTheme.headlineMedium),
        const SizedBox(height: AppSpacing.md),
        _buildTimelineItem(
          'Pickup',
          dateFormat.format(booking.pickupDate),
          isCompleted: pickupDone || tripCompleted,
          isCurrent: booking.status == BookingStatus.confirmed,
        ),
        _buildTimelineItem(
          'Trip Active',
          tripActive
              ? 'Enjoy your ride!'
              : (tripCompleted ? 'Trip completed' : 'Upcoming'),
          isCompleted: tripCompleted,
          isCurrent: tripActive,
        ),
        _buildTimelineItem(
          'Return',
          dateFormat.format(booking.returnDate),
          isCompleted: tripCompleted,
          isLast: !isCancelled,
        ),
        if (isCancelled)
          _buildTimelineItem(
            booking.status == BookingStatus.cancelled
                ? 'Cancelled'
                : 'Rejected',
            'This booking has been ${booking.status.name}',
            isCompleted: false,
            isCurrent: true,
            isLast: true,
          ),
      ],
    );
  }

  Widget _buildTimelineItem(String title, String subtitle,
      {bool isCompleted = false, bool isCurrent = false, bool isLast = false}) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Column(
          children: [
            Container(
              width: 24,
              height: 24,
              decoration: BoxDecoration(
                color: isCompleted || isCurrent
                    ? AppColors.primary
                    : AppColors.surface,
                border: Border.all(
                    color: isCompleted || isCurrent
                        ? AppColors.primary
                        : AppColors.border,
                    width: 2),
                shape: BoxShape.circle,
              ),
              child: isCompleted
                  ? const Icon(LucideIcons.check,
                      size: 14, color: AppColors.surface)
                  : null,
            ),
            if (!isLast)
              Container(
                width: 2,
                height: 40,
                color: isCompleted ? AppColors.primary : AppColors.border,
              ),
          ],
        ),
        const SizedBox(width: AppSpacing.md),
        Expanded(
          child: Padding(
            padding: const EdgeInsets.only(bottom: AppSpacing.xl),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title,
                    style: AppTypography.textTheme.titleMedium?.copyWith(
                        color: isCurrent
                            ? AppColors.primary
                            : AppColors.textPrimary)),
                const SizedBox(height: AppSpacing.xs),
                Text(subtitle, style: AppTypography.textTheme.bodyMedium),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildActionButtons(BuildContext context) {
    return Column(
      children: [
        // Pay Now — for confirmed/pending bookings with unpaid payment status
        if ((booking.status == BookingStatus.confirmed ||
                booking.status == BookingStatus.pending) &&
            !booking.paymentStatus.isPaid) ...[
          PrimaryButton(
            text: _isPaymentLoading ? 'Processing...' : 'Pay Now',
            isLoading: _isPaymentLoading,
            onPressed:
                _isPaymentLoading ? null : () => _showPaymentDialog(context),
            icon: LucideIcons.creditCard,
          ),
          const SizedBox(height: AppSpacing.md),
        ],

        // Cancel — only for pending bookings
        if (booking.status == BookingStatus.pending) ...[
          PrimaryButton(
            text: 'Cancel Reservation',
            backgroundColor: AppColors.error,
            onPressed: () =>
                context.push(AppRoutes.cancelReservation, extra: booking),
            icon: LucideIcons.xCircle,
          ),
          const SizedBox(height: AppSpacing.md),
        ],

        // Start Inspection — active bookings
        if (booking.status == BookingStatus.active) ...[
          PrimaryButton(
            text: 'Start Return Inspection',
            onPressed: () =>
                context.push(AppRoutes.vehicleInspection, extra: true),
            icon: LucideIcons.clipboardCheck,
          ),
          const SizedBox(height: AppSpacing.md),
        ],

        // Write Review — completed bookings
        if (booking.status == BookingStatus.completed) ...[
          PrimaryButton(
            text: 'Write a Review',
            onPressed: () =>
                context.push(AppRoutes.writeReview, extra: booking),
            icon: LucideIcons.star,
          ),
          const SizedBox(height: AppSpacing.md),
        ],

        SecondaryButton(
          text: 'View Rental Agreement',
          onPressed: () => context.push(AppRoutes.rentalAgreement),
          icon: LucideIcons.fileText,
        ),
        const SizedBox(height: AppSpacing.md),
        SecondaryButton(
          text: 'Report an Issue',
          onPressed: () => context.push(AppRoutes.support),
          icon: LucideIcons.alertCircle,
        ),
      ],
    );
  }

  void _showPaymentDialog(BuildContext context) {
    String selectedMethod = 'cash';
    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setDialogState) => AlertDialog(
          title: const Text('Choose Payment Method'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                'Amount: \$${booking.totalAmount.toStringAsFixed(2)}',
                style: AppTypography.textTheme.headlineMedium,
              ),
              const SizedBox(height: AppSpacing.md),
              ...[
                'cash',
                'bank_transfer',
                'card',
                'online_payment'
              ].map((method) => RadioListTile<String>(
                    title: Text(_humanizeMethod(method)),
                    value: method,
                    groupValue: selectedMethod,
                    activeColor: AppColors.primary,
                    onChanged: (v) => setDialogState(() => selectedMethod = v!),
                  )),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Cancel'),
            ),
            TextButton(
              onPressed: () => Navigator.pop(ctx, selectedMethod),
              child:
                  const Text('Pay', style: TextStyle(color: AppColors.primary)),
            ),
          ],
        ),
      ),
    ).then((method) {
      if (method != null && mounted) {
        _processPayment(method as String);
      }
    });
  }

  String _humanizeMethod(String method) {
    switch (method) {
      case 'cash':
        return 'Cash';
      case 'bank_transfer':
        return 'Bank Transfer';
      case 'card':
        return 'Card';
      case 'online_payment':
        return 'Online Payment';
      default:
        return method;
    }
  }

  Future<void> _processPayment(String method) async {
    setState(() => _isPaymentLoading = true);

    final res = await PaymentRepository.instance.createPayment(
      bookingId: booking.id,
      amount: booking.totalAmount,
      paymentMethod: method,
    );

    if (!mounted) return;
    setState(() => _isPaymentLoading = false);

    if (res.success) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Payment submitted successfully!'),
          backgroundColor: AppColors.success,
        ),
      );
      // Pop back so the booking list refreshes
      Navigator.pop(context, true);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(res.error?.friendlyMessage ??
              'Payment failed. Please try again.'),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }
}
