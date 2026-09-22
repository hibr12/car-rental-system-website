import 'dart:async';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../core/routes/app_routes.dart';
import '../../core/utils/formatters.dart';
import '../../data/repositories/payment_repository.dart';
import '../../models/booking_model.dart';
import '../../widgets/buttons/app_buttons.dart';

/// Polls the backend for payment verification after the customer returns
/// from the Chapa checkout page.
///
/// The backend is the authority for payment status — the client never
/// marks a payment as successful locally.
class PaymentStatusScreen extends StatefulWidget {
  final Booking booking;
  final String txRef;

  const PaymentStatusScreen({
    super.key,
    required this.booking,
    required this.txRef,
  });

  @override
  State<PaymentStatusScreen> createState() => _PaymentStatusScreenState();
}

class _PaymentStatusScreenState extends State<PaymentStatusScreen> {
  bool _isVerifying = true;
  String _status = 'pending';
  String _message = 'Verifying your payment…';
  bool _isSuccess = false;
  bool _isFailed = false;

  /// Amount as reported by the VERIFIED payment record (authoritative);
  /// falls back to the booking total while still verifying.
  double? _verifiedAmount;

  Timer? _pollTimer;
  int _pollCount = 0;
  static const int _maxPolls = 12; // 12 × 5s = 60 seconds max

  @override
  void initState() {
    super.initState();
    _verifyPayment();
  }

  @override
  void dispose() {
    _pollTimer?.cancel();
    super.dispose();
  }

  Future<void> _verifyPayment() async {
    setState(() {
      _isVerifying = true;
      _message = 'Verifying your payment…';
    });

    final res = await PaymentRepository.instance.verifyPayment(widget.txRef);

    if (!mounted) return;

    if (res.success && res.data != null) {
      final data = res.data!;
      final paymentStatus = (data['status'] as String?)?.toLowerCase() ?? '';
      final retryable = data['retryable'] == true;

      if (paymentStatus == 'paid' || paymentStatus == 'completed') {
        _handleSuccess(data);
        return;
      }

      if (paymentStatus == 'failed') {
        _handleFailure(data['failure_reason'] as String?);
        return;
      }

      if (retryable || paymentStatus == 'pending') {
        // Start polling
        _startPolling();
        return;
      }

      // Unknown status — show as pending
      _startPolling();
    } else {
      // Verification request itself failed — try polling
      _startPolling();
    }
  }

  void _startPolling() {
    _pollTimer?.cancel();
    _pollCount = 0;

    setState(() {
      _isVerifying = true;
      _message = 'Payment is being processed. This may take a moment…';
    });

    _pollTimer = Timer.periodic(const Duration(seconds: 5), (timer) async {
      _pollCount++;

      if (_pollCount >= _maxPolls) {
        timer.cancel();
        if (mounted) {
          setState(() {
            _isVerifying = false;
            _status = 'pending';
            _message =
                'Payment verification is taking longer than expected. '
                'You can check your booking status later.';
          });
        }
        return;
      }

      final res = await PaymentRepository.instance.verifyPayment(widget.txRef);

      if (!mounted) return;

      if (res.success && res.data != null) {
        final data = res.data!;
        final paymentStatus = (data['status'] as String?)?.toLowerCase() ?? '';

        if (paymentStatus == 'paid' || paymentStatus == 'completed') {
          timer.cancel();
          _handleSuccess(data);
          return;
        }

        if (paymentStatus == 'failed') {
          timer.cancel();
          _handleFailure(data['failure_reason'] as String?);
          return;
        }
      }
    });
  }

  void _handleSuccess(Map<String, dynamic> data) {
    final amount = (data['amount'] as num?)?.toDouble();
    setState(() {
      _isVerifying = false;
      _isSuccess = true;
      _status = 'paid';
      _verifiedAmount = amount;
      _message = 'Payment completed successfully! Your booking is now '
          'awaiting branch confirmation.';
    });
  }

  void _handleFailure(String? reason) {
    setState(() {
      _isVerifying = false;
      _isFailed = true;
      _status = 'failed';
      _message = reason ?? 'Payment was not successful. Please try again.';
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('Payment Status')),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(AppSpacing.pagePadding),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              const SizedBox(height: AppSpacing.xxxl),

              // Status icon
              _buildStatusIcon(),
              const SizedBox(height: AppSpacing.xxl),

              // Status title
              Text(
                _isSuccess
                    ? 'Payment Successful'
                    : _isFailed
                        ? 'Payment Failed'
                        : 'Verifying Payment',
                style: AppTypography.textTheme.displaySmall,
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: AppSpacing.md),

              // Status message
              Text(
                _message,
                style: AppTypography.textTheme.bodyLarge
                    ?.copyWith(color: AppColors.textSecondary),
                textAlign: TextAlign.center,
              ),

              const SizedBox(height: AppSpacing.xxl),

              // Transaction reference
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(AppSpacing.md),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
                  border: Border.all(color: AppColors.border),
                ),
                child: Column(
                  children: [
                    _buildInfoRow('Transaction Ref', widget.txRef),
                    const Divider(height: AppSpacing.lg),
                    _buildInfoRow('Booking Ref',
                        widget.booking.bookingReference.isNotEmpty
                            ? widget.booking.bookingReference
                            : 'N/A'),
                    const Divider(height: AppSpacing.lg),
                    _buildInfoRow('Amount',
                        Formatters.etb(_verifiedAmount ?? widget.booking.totalAmount)),
                    const Divider(height: AppSpacing.lg),
                    _buildInfoRow('Status', _status.toUpperCase()),
                  ],
                ),
              ),

              const SizedBox(height: AppSpacing.xxxl),

              // Action buttons
              if (_isSuccess) ...[
                PrimaryButton(
                  text: 'View My Bookings',
                  onPressed: () => context.go(AppRoutes.home),
                ),
              ] else if (_isFailed) ...[
                PrimaryButton(
                  text: 'Try Again',
                  onPressed: () => context.pop(),
                ),
                const SizedBox(height: AppSpacing.md),
                SecondaryButton(
                  text: 'View My Bookings',
                  onPressed: () => context.go(AppRoutes.home),
                ),
              ] else if (!_isVerifying) ...[
                PrimaryButton(
                  text: 'Check Again',
                  onPressed: _verifyPayment,
                ),
                const SizedBox(height: AppSpacing.md),
                SecondaryButton(
                  text: 'View My Bookings',
                  onPressed: () => context.go(AppRoutes.home),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStatusIcon() {
    if (_isVerifying) {
      return Container(
        padding: const EdgeInsets.all(AppSpacing.xxl),
        decoration: BoxDecoration(
          color: AppColors.primary.withOpacity(0.1),
          shape: BoxShape.circle,
        ),
        child: const SizedBox(
          width: 64,
          height: 64,
          child: CircularProgressIndicator(
            strokeWidth: 3,
            color: AppColors.primary,
          ),
        ),
      );
    }

    if (_isSuccess) {
      return Container(
        padding: const EdgeInsets.all(AppSpacing.xxl),
        decoration: BoxDecoration(
          color: AppColors.success.withOpacity(0.1),
          shape: BoxShape.circle,
        ),
        child: const Icon(LucideIcons.checkCircle,
            size: 64, color: AppColors.success),
      );
    }

    return Container(
      padding: const EdgeInsets.all(AppSpacing.xxl),
      decoration: BoxDecoration(
        color: AppColors.error.withOpacity(0.1),
        shape: BoxShape.circle,
      ),
      child: const Icon(LucideIcons.xCircle, size: 64, color: AppColors.error),
    );
  }

  Widget _buildInfoRow(String label, String value) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label,
            style: AppTypography.textTheme.bodyMedium
                ?.copyWith(color: AppColors.textSecondary)),
        Flexible(
          child: Text(value,
              style: AppTypography.textTheme.titleSmall,
              textAlign: TextAlign.end),
        ),
      ],
    );
  }
}
