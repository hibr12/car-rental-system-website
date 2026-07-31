import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../core/routes/app_routes.dart';
import '../../models/booking_draft.dart';
import '../../models/vehicle_model.dart';
import '../../widgets/buttons/app_buttons.dart';
import 'components/booking_date_components.dart';

class BookingDateScreen extends StatefulWidget {
  final Vehicle vehicle;

  const BookingDateScreen({super.key, required this.vehicle});

  @override
  State<BookingDateScreen> createState() => _BookingDateScreenState();
}

class _BookingDateScreenState extends State<BookingDateScreen> {
  DateTime? _startDate = DateTime.now().add(const Duration(days: 1));
  DateTime? _endDate = DateTime.now().add(const Duration(days: 3));
  TimeOfDay? _startTime = const TimeOfDay(hour: 10, minute: 0);
  TimeOfDay? _endTime = const TimeOfDay(hour: 10, minute: 0);

  void _selectDateRange() async {
    final initialDateRange = DateTimeRange(
      start: DateTime.now().add(const Duration(days: 1)),
      end: DateTime.now().add(const Duration(days: 3)),
    );

    final newDateRange = await showDateRangePicker(
      context: context,
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 365)),
      initialDateRange: initialDateRange,
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: AppColors.primary,
              onPrimary: AppColors.surface,
              onSurface: AppColors.textPrimary,
            ),
          ),
          child: child!,
        );
      },
    );

    if (newDateRange != null) {
      setState(() {
        _startDate = newDateRange.start;
        _endDate = newDateRange.end;
      });
    }
  }

  void _selectTime(bool isStart) async {
    final newTime = await showTimePicker(
      context: context,
      initialTime: const TimeOfDay(hour: 10, minute: 0),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: AppColors.primary,
            ),
          ),
          child: child!,
        );
      },
    );

    if (newTime != null) {
      setState(() {
        if (isStart) {
          _startTime = newTime;
        } else {
          _endTime = newTime;
        }
      });
    }
  }

  bool get _canProceed =>
      _startDate != null &&
      _endDate != null &&
      _startTime != null &&
      _endTime != null;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Select Dates'),
      ),
      body: SafeArea(
        child: Column(
          children: [
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(AppSpacing.pagePadding),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    VehicleSummaryCard(vehicle: widget.vehicle),
                    const SizedBox(height: AppSpacing.xxl),
                    Text('Trip Dates',
                        style: AppTypography.textTheme.headlineMedium),
                    const SizedBox(height: AppSpacing.md),
                    DateSelectorCard(
                      startDate: _startDate,
                      endDate: _endDate,
                      onTap: _selectDateRange,
                    ),
                    const SizedBox(height: AppSpacing.xxl),
                    Text('Pickup & Return Time',
                        style: AppTypography.textTheme.headlineMedium),
                    const SizedBox(height: AppSpacing.md),
                    Row(
                      children: [
                        Expanded(
                          child: TimeSelectorCard(
                            time: _startTime,
                            isStart: true,
                            onTap: () => _selectTime(true),
                          ),
                        ),
                        const SizedBox(width: AppSpacing.md),
                        Expanded(
                          child: TimeSelectorCard(
                            time: _endTime,
                            isStart: false,
                            onTap: () => _selectTime(false),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: AppSpacing.xxl),
                    LocationInfoCard(location: widget.vehicle.location),
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
        text: 'Continue to Checkout',
        onPressed: _canProceed
            ? () {
                final draft = BookingDraft(
                  vehicle: widget.vehicle,
                  pickupDate: _startDate!,
                  returnDate: _endDate!,
                  pickupTime: _startTime!,
                  returnTime: _endTime!,
                  pickupLocation: widget.vehicle.location,
                  returnLocation: widget.vehicle.location,
                );
                context.push(AppRoutes.bookingSummary, extra: draft);
              }
            : null,
      ),
    );
  }
}
