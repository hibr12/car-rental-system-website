import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../core/routes/app_routes.dart';
import '../../core/utils/formatters.dart';
import '../../models/booking_model.dart';
import '../../widgets/buttons/app_buttons.dart';
import '../../widgets/states/error_state_widget.dart';
import '../../widgets/states/status_badge.dart';
import 'package:go_router/go_router.dart';
import '../../data/repositories/booking_repository.dart';

class ReservationDetailsScreen extends StatefulWidget {
  final Booking booking;

  const ReservationDetailsScreen({super.key, required this.booking});

  @override
  State<ReservationDetailsScreen> createState() =>
      _ReservationDetailsScreenState();
}

class _ReservationDetailsScreenState extends State<ReservationDetailsScreen> {
  late Booking _booking;
  bool _isRefreshing = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _booking = widget.booking;
    _refresh();
  }

  /// Re-fetches the booking so status/payment state are authoritative.
  Future<void> _refresh() async {
    setState(() {
      _isRefreshing = true;
      _error = null;
    });
    final res = await BookingRepository.instance.getBookingById(_booking.id);
    if (!mounted) return;
    setState(() {
      if (res.success && res.data != null) {
        _booking = res.data!;
      } else if (res.error != null) {
        _error = res.error!.friendlyMessage;
      }
      _isRefreshing = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Reservation Details'),
        actions: [
          IconButton(
            icon: const Icon(LucideIcons.refreshCw, size: 20),
            onPressed: _isRefreshing ? null : _refresh,
          ),
        ],
      ),
      body: _isRefreshing && _error == null
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? ErrorStateWidget(message: _error!, onRetry: _refresh)
              : SingleChildScrollView(
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

  BadgeStatus get _badgeStatus {
    switch (_booking.status) {
      case BookingStatus.pendingPayment:
      case BookingStatus.paymentRequired:
      case BookingStatus.pendingBranchApproval:
      case BookingStatus.pendingAdminApproval:
      case BookingStatus.returnPending:
        return BadgeStatus.pending;
      case BookingStatus.confirmed:
      case BookingStatus.readyForPickup:
      case BookingStatus.active:
        return BadgeStatus.active;
      case BookingStatus.completed:
        return BadgeStatus.completed;
      case BookingStatus.cancelled:
      case BookingStatus.rejected:
      case BookingStatus.expired:
        return BadgeStatus.cancelled;
      default:
        return BadgeStatus.pending;
    }
  }

  Widget _buildStatusHeader() {
    final rejection = _booking.rejectionReason;
    final cancellation = _booking.cancellationReason;

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(AppSpacing.lg),
      decoration: BoxDecoration(
        color: AppColors.primaryLight,
        borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
      ),
      child: Column(
        children: [
          StatusBadge(label: _booking.status.label, status: _badgeStatus),
          const SizedBox(height: AppSpacing.md),
          Text('Booking Reference',
              style: AppTypography.textTheme.bodyMedium
                  ?.copyWith(color: AppColors.primary)),
          const SizedBox(height: AppSpacing.xs),
          Text(
            _booking.bookingReference.isNotEmpty
                ? _booking.bookingReference
                : 'N/A',
            style: AppTypography.textTheme.headlineMedium
                ?.copyWith(color: AppColors.primary),
          ),
          if (rejection != null && rejection.isNotEmpty) ...[
            const SizedBox(height: AppSpacing.md),
            Text('Rejected: $rejection',
                textAlign: TextAlign.center,
                style: AppTypography.textTheme.bodySmall
                    ?.copyWith(color: AppColors.error)),
          ],
          if (cancellation != null && cancellation.isNotEmpty) ...[
            const SizedBox(height: AppSpacing.sm),
            Text('Cancelled: $cancellation',
                textAlign: TextAlign.center,
                style: AppTypography.textTheme.bodySmall
                    ?.copyWith(color: AppColors.textSecondary)),
          ],
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
            _booking.vehicle.imageUrls.first,
            width: 100,
            height: 70,
            fit: BoxFit.cover,
            errorBuilder: (_, __, ___) => Container(
              width: 100,
              height: 70,
              color: AppColors.backgroundSecondary,
              child: const Icon(LucideIcons.imageOff, size: 24),
            ),
          ),
        ),
        const SizedBox(width: AppSpacing.md),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(_booking.vehicle.fullName,
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
                      _booking.pickupLocation.isNotEmpty
                          ? _booking.pickupLocation
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
              'Total Amount', Formatters.etb(_booking.totalAmount)),
          const SizedBox(height: AppSpacing.sm),
          _buildDetailRow('Payment Status', _booking.paymentStatus.label),
          if (_booking.numberOfDays > 0) ...[
            const SizedBox(height: AppSpacing.sm),
            _buildDetailRow('Duration', '${_booking.numberOfDays} day(s)'),
          ],
          if (_booking.pricePerDay > 0) ...[
            const SizedBox(height: AppSpacing.sm),
            _buildDetailRow(
                'Daily Rate', Formatters.etb(_booking.pricePerDay)),
          ],
          if (_booking.branchName.isNotEmpty) ...[
            const SizedBox(height: AppSpacing.sm),
            _buildDetailRow('Branch', _booking.branchName),
          ],
          if (_booking.notes.isNotEmpty) ...[
            const SizedBox(height: AppSpacing.sm),
            _buildDetailRow('Notes', _booking.notes),
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
    final bool pickupDone =
        _booking.pickedUpAt != null || _booking.status == BookingStatus.active;
    final bool tripActive = _booking.status == BookingStatus.active;
    final bool tripCompleted = _booking.status == BookingStatus.completed;
    final bool isTerminal = _booking.status.isPast;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('Rental Timeline', style: AppTypography.textTheme.headlineMedium),
        const SizedBox(height: AppSpacing.md),
        _buildTimelineItem(
          'Pickup',
          Formatters.date(_booking.pickupDate),
          isCompleted: pickupDone || tripCompleted,
          isCurrent: !isTerminal && !pickupDone,
        ),
        _buildTimelineItem(
          'Trip Active',
          tripActive
              ? 'Enjoy your ride!'
              : (tripCompleted
                  ? 'Trip completed'
                  : (pickupDone ? 'In progress' : 'Upcoming')),
          isCompleted: tripCompleted,
          isCurrent: tripActive,
        ),
        _buildTimelineItem(
          'Return',
          Formatters.date(_booking.returnDate),
          isCompleted: tripCompleted,
          isLast: !isTerminal,
        ),
        if (isTerminal)
          _buildTimelineItem(
            _booking.status.label,
            switch (_booking.status) {
              BookingStatus.cancelled =>
                _booking.cancelledAt != null
                    ? 'Cancelled ${Formatters.date(_booking.cancelledAt)}'
                    : 'This booking was cancelled.',
              BookingStatus.rejected => 'This booking was not approved.',
              _ => 'This booking has ended.',
            },
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
        // Cash-pending notice — payment awaits branch confirmation.
        if (_booking.paymentStatus == PaymentStatus.cashPending)
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(AppSpacing.md),
            margin: const EdgeInsets.only(bottom: AppSpacing.md),
            decoration: BoxDecoration(
              color: AppColors.warning.withOpacity(0.08),
              borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
              border: Border.all(color: AppColors.warning.withOpacity(0.4)),
            ),
            child: Row(
              children: [
                const Icon(LucideIcons.banknote,
                    size: 18, color: AppColors.warning),
                const SizedBox(width: AppSpacing.sm),
                Expanded(
                  child: Text(
                    'Cash payment registered — visit the branch to pay. '
                    'Staff will confirm and finalize your booking.',
                    style: AppTypography.textTheme.bodySmall
                        ?.copyWith(color: AppColors.textSecondary),
                  ),
                ),
              ],
            ),
          ),

        // Pay online — only when the backend expects money.
        if (_booking.canPayOnline) ...[
          PrimaryButton(
            text: 'Pay Now (Chapa)',
            onPressed: () async {
              await context.push(AppRoutes.payment, extra: _booking);
              if (mounted) _refresh();
            },
            icon: LucideIcons.creditCard,
          ),
          const SizedBox(height: AppSpacing.md),
        ],

        // Cancel — only while cancellable per backend rules.
        if (_booking.status.isCancellable &&
            !_booking.paymentStatus.isPaid) ...[
          SecondaryButton(
            text: 'Cancel Reservation',
            onPressed: () async {
              await context.push(AppRoutes.cancelReservation, extra: _booking);
              if (mounted) _refresh();
            },
            icon: LucideIcons.xCircle,
          ),
          const SizedBox(height: AppSpacing.md),
        ],

        // Review — completed rentals without a review yet.
        if (_booking.status == BookingStatus.completed &&
            !_booking.hasReview) ...[
          PrimaryButton(
            text: 'Write a Review',
            onPressed: () async {
              await context.push(AppRoutes.writeReview, extra: _booking);
              if (mounted) _refresh();
            },
            icon: LucideIcons.star,
          ),
          const SizedBox(height: AppSpacing.md),
        ],

        SecondaryButton(
          text: 'Report an Issue',
          onPressed: () => context.push(AppRoutes.support),
          icon: LucideIcons.alertCircle,
        ),
      ],
    );
  }
}
