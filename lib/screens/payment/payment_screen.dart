import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../core/routes/app_routes.dart';
import '../../core/utils/formatters.dart';
import '../../data/repositories/payment_repository.dart';
import '../../models/booking_model.dart';
import '../../widgets/buttons/app_buttons.dart';

/// Initiates a payment for a booking.
///
/// Two backend-supported flows:
///  * Online (Chapa): initialize → open checkout in browser → return →
///    the status screen verifies the transaction server-side.
///  * Cash: creates a `cash_pending` payment that branch staff confirm
///    in person. The app NEVER marks it paid by itself.
class PaymentScreen extends StatefulWidget {
  final Booking booking;

  const PaymentScreen({super.key, required this.booking});

  @override
  State<PaymentScreen> createState() => _PaymentScreenState();
}

class _PaymentScreenState extends State<PaymentScreen> {
  bool _isInitializing = false;
  bool _isPayingCash = false;
  String? _error;
  String? _txRef;
  String? _checkoutUrl;

  Future<void> _initializePayment() async {
    setState(() {
      _isInitializing = true;
      _error = null;
    });

    final res = await PaymentRepository.instance.initializePayment(
      bookingId: widget.booking.id,
    );

    if (!mounted) return;

    if (res.success && res.data != null) {
      final data = res.data!;
      final checkoutUrl = data['checkout_url'] as String?;
      final txRef = data['tx_ref'] as String?;

      if (checkoutUrl != null && checkoutUrl.isNotEmpty) {
        setState(() {
          _checkoutUrl = checkoutUrl;
          _txRef = txRef;
          _isInitializing = false;
        });
        _openCheckout(checkoutUrl);
      } else {
        setState(() {
          _isInitializing = false;
          _error = 'Could not get payment checkout URL. Please try again.';
        });
      }
    } else {
      setState(() {
        _isInitializing = false;
        _error = res.error?.friendlyMessage ??
            'Failed to initialize payment. Please try again.';
      });
    }
  }

  Future<void> _openCheckout(String url) async {
    final uri = Uri.parse(url);
    try {
      final launched = await launchUrl(uri, mode: LaunchMode.externalApplication);
      if (!launched && mounted) {
        setState(() {
          _error = 'Could not open the payment page. Please try again.';
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = 'Could not open the payment page. Please try again.';
        });
      }
    }
  }

  void _checkPaymentStatus() {
    if (_txRef != null) {
      context.push(AppRoutes.paymentStatus, extra: {
        'booking': widget.booking,
        'tx_ref': _txRef,
      });
    }
  }

  /// Real backend flow: `POST /payments { payment_method: 'cash' }` →
  /// `cash_pending` until staff confirm at the branch.
  Future<void> _payWithCash() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Pay with Cash'),
        content: const Text(
          'Your booking will be marked as "cash pending". Please visit the '
          'branch to pay — staff will confirm your payment and finalize the '
          'booking. Continue?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Continue',
                style: TextStyle(color: AppColors.primary)),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;

    setState(() {
      _isPayingCash = true;
      _error = null;
    });

    final res = await PaymentRepository.instance.payWithCash(
      bookingId: widget.booking.id,
    );

    if (!mounted) return;
    setState(() => _isPayingCash = false);

    if (res.success) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
              'Cash payment registered. The branch will confirm it when you pay.'),
          backgroundColor: AppColors.success,
        ),
      );
      // Booking state changed server-side — refresh upstream screens.
      context.pop(true);
    } else {
      setState(() {
        _error = res.error?.friendlyMessage ??
            'Could not register cash payment. Please try again.';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('Payment')),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(AppSpacing.pagePadding),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              const SizedBox(height: AppSpacing.xl),

              // Booking summary card
              _buildBookingCard(),
              const SizedBox(height: AppSpacing.xxl),

              // Payment amount
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(AppSpacing.lg),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
                  border: Border.all(color: AppColors.primary.withOpacity(0.3)),
                ),
                child: Column(
                  children: [
                    Text('Amount Due',
                        style: AppTypography.textTheme.bodyMedium
                            ?.copyWith(color: AppColors.textSecondary)),
                    const SizedBox(height: AppSpacing.xs),
                    Text(
                      Formatters.etb(widget.booking.totalAmount),
                      style: AppTypography.textTheme.displayMedium
                          ?.copyWith(color: AppColors.primary),
                    ),
                    const SizedBox(height: AppSpacing.sm),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(LucideIcons.shieldCheck,
                            size: 14, color: AppColors.success),
                        const SizedBox(width: AppSpacing.xs),
                        Text('Secure payment via Chapa',
                            style: AppTypography.textTheme.bodySmall
                                ?.copyWith(color: AppColors.textSecondary)),
                      ],
                    ),
                  ],
                ),
              ),

              const SizedBox(height: AppSpacing.lg),

              // Error message
              if (_error != null) ...[
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(AppSpacing.md),
                  decoration: BoxDecoration(
                    color: AppColors.error.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
                  ),
                  child: Row(
                    children: [
                      const Icon(LucideIcons.alertCircle,
                          size: 16, color: AppColors.error),
                      const SizedBox(width: AppSpacing.sm),
                      Expanded(
                        child: Text(
                          _error!,
                          style: AppTypography.textTheme.bodySmall
                              ?.copyWith(color: AppColors.error),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: AppSpacing.lg),
              ],

              // Action buttons
              if (_checkoutUrl == null) ...[
                // Initial state — online payment entry point.
                PrimaryButton(
                  text: 'Pay Now with Chapa',
                  icon: LucideIcons.creditCard,
                  isLoading: _isInitializing,
                  onPressed: _isInitializing ? null : _initializePayment,
                ),
                const SizedBox(height: AppSpacing.md),
                SecondaryButton(
                  text: 'Pay with Cash at Branch',
                  icon: LucideIcons.banknote,
                  isLoading: _isPayingCash,
                  onPressed:
                      (_isPayingCash || _isInitializing) ? null : _payWithCash,
                ),
              ] else ...[
                // After checkout URL obtained
                PrimaryButton(
                  text: 'I Have Completed Payment',
                  icon: LucideIcons.checkCircle,
                  onPressed: _checkPaymentStatus,
                ),
                const SizedBox(height: AppSpacing.md),
                SecondaryButton(
                  text: 'Open Payment Page Again',
                  icon: LucideIcons.externalLink,
                  onPressed: () => _openCheckout(_checkoutUrl!),
                ),
              ],

              const SizedBox(height: AppSpacing.xxl),

              // Payment info
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(AppSpacing.md),
                decoration: BoxDecoration(
                  color: AppColors.surfaceElevated,
                  borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('How it works',
                        style: AppTypography.textTheme.titleMedium),
                    const SizedBox(height: AppSpacing.sm),
                    _buildStep('1', 'Tap "Pay Now" to open the Chapa payment page'),
                    const SizedBox(height: AppSpacing.xs),
                    _buildStep('2', 'Complete payment using your preferred method'),
                    const SizedBox(height: AppSpacing.xs),
                    _buildStep('3', 'Return here and tap "I Have Completed Payment"'),
                    const SizedBox(height: AppSpacing.xs),
                    _buildStep('4', 'We will verify your payment automatically'),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildBookingCard() {
    final vehicle = widget.booking.vehicle;
    return Container(
      width: double.infinity,
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
              width: 64,
              height: 48,
              fit: BoxFit.cover,
              errorBuilder: (_, __, ___) => Container(
                width: 64,
                height: 48,
                color: AppColors.surfaceElevated,
                child: const Icon(LucideIcons.car, size: 20),
              ),
            ),
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(vehicle.fullName,
                    style: AppTypography.textTheme.titleMedium,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis),
                if (widget.booking.bookingReference.isNotEmpty) ...[
                  const SizedBox(height: AppSpacing.xs),
                  Text('Ref: ${widget.booking.bookingReference}',
                      style: AppTypography.textTheme.bodySmall
                          ?.copyWith(color: AppColors.textSecondary)),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStep(String number, String text) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 20,
          height: 20,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: AppColors.primary.withOpacity(0.1),
            shape: BoxShape.circle,
          ),
          child: Text(number,
              style: AppTypography.textTheme.labelSmall
                  ?.copyWith(color: AppColors.primary)),
        ),
        const SizedBox(width: AppSpacing.sm),
        Expanded(
          child: Text(text, style: AppTypography.textTheme.bodySmall),
        ),
      ],
    );
  }
}
