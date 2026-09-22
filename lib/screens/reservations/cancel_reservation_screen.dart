import 'package:flutter/material.dart';

import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../widgets/buttons/app_buttons.dart';
import '../../models/booking_model.dart';
import '../../data/repositories/booking_repository.dart';

class CancelReservationScreen extends StatefulWidget {
  final Booking booking;
  const CancelReservationScreen({super.key, required this.booking});

  @override
  State<CancelReservationScreen> createState() =>
      _CancelReservationScreenState();
}

class _CancelReservationScreenState extends State<CancelReservationScreen> {
  String? _selectedReason;
  final TextEditingController _detailsController = TextEditingController();
  bool _isCancelling = false;

  final List<String> _reasons = [
    'My plans changed',
    'Found a better vehicle',
    'Travel delays / Flight cancelled',
    'Personal emergency',
    'Other',
  ];

  @override
  void dispose() {
    _detailsController.dispose();
    super.dispose();
  }

  void _confirmCancel() {
    if (_selectedReason == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
            content: Text('Please select a reason for cancellation.')),
      );
      return;
    }

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Cancel Reservation?'),
        content: const Text(
            'Are you sure you want to cancel this reservation? This action cannot be undone.'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Keep Reservation')),
          TextButton(
            onPressed: () {
              Navigator.pop(ctx); // Close dialog
              _performCancel();
            },
            child: const Text('Yes, Cancel',
                style: TextStyle(color: AppColors.error)),
          ),
        ],
      ),
    );
  }

  Future<void> _performCancel() async {
    setState(() => _isCancelling = true);

    // Backend accepts an optional reason (max 500 chars).
    var reason = _selectedReason ?? '';
    if (_selectedReason == 'Other' && _detailsController.text.trim().isNotEmpty) {
      reason = '${_selectedReason!}: ${_detailsController.text.trim()}';
    }
    if (reason.length > 500) reason = reason.substring(0, 500);

    final res = await BookingRepository.instance
        .cancelBooking(widget.booking.id, reason: reason);

    if (!mounted) return;
    setState(() => _isCancelling = false);

    if (res.success) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Reservation cancelled successfully'),
          backgroundColor: AppColors.success,
        ),
      );
      // Pop with true so the reservations list refreshes
      Navigator.pop(context, true);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            res.error?.friendlyMessage ??
                'Failed to cancel reservation. Please try again.',
          ),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('Cancel Reservation')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(AppSpacing.pagePadding),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Why are you cancelling?',
                style: AppTypography.textTheme.headlineMedium),
            const SizedBox(height: AppSpacing.md),
            ..._reasons.map((reason) {
              return RadioListTile<String>(
                title: Text(reason, style: AppTypography.textTheme.bodyLarge),
                value: reason,
                groupValue: _selectedReason,
                activeColor: AppColors.primary,
                contentPadding: EdgeInsets.zero,
                onChanged: (val) => setState(() => _selectedReason = val),
              );
            }),
            if (_selectedReason == 'Other') ...[
              const SizedBox(height: AppSpacing.md),
              TextField(
                controller: _detailsController,
                maxLines: 3,
                decoration: InputDecoration(
                  hintText: 'Please tell us more...',
                  filled: true,
                  fillColor: AppColors.surface,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
                    borderSide: const BorderSide(color: AppColors.border),
                  ),
                ),
              ),
            ],
            const SizedBox(height: AppSpacing.xxxl),
            Container(
              padding: const EdgeInsets.all(AppSpacing.md),
              decoration: BoxDecoration(
                color: AppColors.warning.withOpacity(0.1),
                borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
                border: Border.all(color: AppColors.warning.withOpacity(0.3)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.info_outline, color: AppColors.warning),
                  const SizedBox(width: AppSpacing.md),
                  Expanded(
                    child: Text(
                      'If you have already paid, our team will review your '
                      'refund after the cancellation. Contact support for '
                      'assistance.',
                      style: AppTypography.textTheme.bodyMedium
                          ?.copyWith(color: AppColors.textPrimary),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: AppSpacing.xxxl),
            PrimaryButton(
              text: 'Review Cancellation',
              isLoading: _isCancelling,
              onPressed: _isCancelling ? null : _confirmCancel,
            ),
          ],
        ),
      ),
    );
  }
}
