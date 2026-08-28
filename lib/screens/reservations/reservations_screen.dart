import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../core/utils/formatters.dart';
import '../../models/booking_model.dart';
import '../../widgets/states/empty_state_widget.dart';
import '../../widgets/states/error_state_widget.dart';
import 'package:go_router/go_router.dart';
import '../../core/routes/app_routes.dart';
import '../../data/repositories/booking_repository.dart';

class ReservationsScreen extends StatefulWidget {
  const ReservationsScreen({super.key});

  @override
  State<ReservationsScreen> createState() => _ReservationsScreenState();
}

class _ReservationsScreenState extends State<ReservationsScreen> {
  List<Booking> _upcomingBookings = [];
  List<Booking> _pastBookings = [];
  bool _isLoading = true;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _fetchBookings();
  }

  Future<void> _fetchBookings() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });
    final res = await BookingRepository.instance.getUserBookings();
    if (mounted) {
      setState(() {
        if (res.success && res.data != null) {
          final allBookings = res.data!;
          _upcomingBookings =
              allBookings.where((b) => b.status.isUpcoming).toList();
          _pastBookings = allBookings.where((b) => b.status.isPast).toList();
          _errorMessage = null;
        } else {
          _upcomingBookings = [];
          _pastBookings = [];
          _errorMessage =
              res.error?.friendlyMessage ?? 'Failed to load bookings';
        }
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 2,
      child: Scaffold(
        backgroundColor: AppColors.background,
        appBar: AppBar(
          title: const Text('My Bookings'),
          bottom: const TabBar(
            labelColor: AppColors.primary,
            unselectedLabelColor: AppColors.textTertiary,
            indicatorColor: AppColors.primary,
            tabs: [
              Tab(text: 'Upcoming'),
              Tab(text: 'Past'),
            ],
          ),
        ),
        body: _isLoading
            ? const Center(child: CircularProgressIndicator())
            : _errorMessage != null
                ? ErrorStateWidget(
                    message: _errorMessage!,
                    onRetry: _fetchBookings,
                  )
                : TabBarView(
                    children: [
                      _buildBookingsList(context, _upcomingBookings),
                      _buildBookingsList(context, _pastBookings),
                    ],
                  ),
      ),
    );
  }

  Widget _buildBookingsList(BuildContext context, List<Booking> bookings) {
    if (bookings.isEmpty) {
      return EmptyStateWidget(
        icon: LucideIcons.calendar,
        title: 'No bookings found',
        message: 'Your trips will appear here.',
        actionText: 'Find a Car',
        onAction: () => context.go(AppRoutes.home),
      );
    }

    return RefreshIndicator(
      onRefresh: _fetchBookings,
      child: ListView.builder(
        padding: const EdgeInsets.all(AppSpacing.pagePadding),
        itemCount: bookings.length,
        itemBuilder: (context, index) {
          final booking = bookings[index];
          return InkWell(
            onTap: () async {
              final result = await context
                  .push<bool>(AppRoutes.reservationDetails, extra: booking);
              // Refresh if booking was modified (cancelled, paid, etc.)
              if (result == true) _fetchBookings();
            },
            child: Card(
              margin: const EdgeInsets.only(bottom: AppSpacing.md),
              child: Padding(
                padding: const EdgeInsets.all(AppSpacing.md),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Flexible(
                          child: Text(
                            'Ref: ${booking.bookingReference}',
                            style: AppTypography.textTheme.labelSmall,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        _buildStatusChip(booking.status),
                      ],
                    ),
                    const Divider(),
                    Row(
                      children: [
                        ClipRRect(
                          borderRadius:
                              BorderRadius.circular(AppSpacing.radiusSm),
                          child: Image.network(
                            booking.vehicle.imageUrls.first,
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
                              Text(booking.vehicle.fullName,
                                  style: AppTypography.textTheme.titleLarge,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis),
                              const SizedBox(height: AppSpacing.xs),
                              Text(
                                '${Formatters.date(booking.pickupDate)} - ${Formatters.date(booking.returnDate)}',
                                style: AppTypography.textTheme.bodyMedium,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: AppSpacing.md),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text('Total Amount',
                            style: AppTypography.textTheme.bodyLarge),
                        Text(
                          Formatters.etb(booking.totalAmount),
                          style: AppTypography.textTheme.headlineMedium
                              ?.copyWith(color: AppColors.primary),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildStatusChip(BookingStatus status) {
    Color bgColor;
    Color textColor;

    switch (status) {
      case BookingStatus.pendingPayment:
      case BookingStatus.paymentRequired:
      case BookingStatus.paymentProcessing:
      case BookingStatus.pendingBranchApproval:
      case BookingStatus.pendingAdminApproval:
      case BookingStatus.returnPending:
        bgColor = AppColors.warning.withOpacity(0.1);
        textColor = AppColors.warning;
        break;
      case BookingStatus.confirmed:
      case BookingStatus.readyForPickup:
      case BookingStatus.paymentVerified:
      case BookingStatus.active:
        bgColor = AppColors.primaryLight;
        textColor = AppColors.primary;
        break;
      case BookingStatus.completed:
        bgColor = AppColors.success.withOpacity(0.1);
        textColor = AppColors.success;
        break;
      case BookingStatus.cancelled:
      case BookingStatus.rejected:
      case BookingStatus.expired:
        bgColor = AppColors.error.withOpacity(0.1);
        textColor = AppColors.error;
        break;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
      ),
      child: Text(
        status.label.toUpperCase(),
        style: AppTypography.textTheme.labelSmall?.copyWith(color: textColor),
      ),
    );
  }
}
