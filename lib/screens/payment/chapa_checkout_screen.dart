import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:webview_flutter/webview_flutter.dart';
import '../../core/colors/app_colors.dart';
import '../../core/routes/app_routes.dart';
import '../../models/booking_model.dart';

/// In-app WebView that loads the Chapa checkout page.
///
/// Monitors navigation and detects when Chapa redirects to the return URL
/// (which contains `tx_ref` and `booking_id` query params). When detected,
/// the WebView is closed and the user is routed to [PaymentStatusScreen]
/// for automatic server-side verification.
class ChapaCheckoutScreen extends StatefulWidget {
  final Booking booking;
  final String checkoutUrl;
  final String txRef;

  const ChapaCheckoutScreen({
    super.key,
    required this.booking,
    required this.checkoutUrl,
    required this.txRef,
  });

  @override
  State<ChapaCheckoutScreen> createState() => _ChapaCheckoutScreenState();
}

class _ChapaCheckoutScreenState extends State<ChapaCheckoutScreen> {
  late final WebViewController _controller;
  bool _isLoading = true;
  bool _paymentDetected = false;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (_) {
            if (mounted) setState(() => _isLoading = true);
          },
          onPageFinished: (_) {
            if (mounted) setState(() => _isLoading = false);
          },
          onNavigationRequest: _onNavigationRequest,
        ),
      )
      ..loadRequest(Uri.parse(widget.checkoutUrl));
  }

  NavigationDecision _onNavigationRequest(NavigationRequest request) {
    if (_paymentDetected) return NavigationDecision.navigate;

    final uri = Uri.parse(request.url);

    // Detect Chapa return URL redirect — contains tx_ref param
    if (uri.queryParameters.containsKey('tx_ref')) {
      _handlePaymentComplete(uri);
      return NavigationDecision.prevent;
    }

    // Also detect if Chapa redirects to a success/thank-you page
    final url = request.url.toLowerCase();
    if (url.contains('thank') ||
        url.contains('success') ||
        url.contains('complete') ||
        url.contains('callback') ||
        url.contains('/payments/verify')) {
      _handlePaymentComplete(uri);
      return NavigationDecision.prevent;
    }

    return NavigationDecision.navigate;
  }

  void _handlePaymentComplete(Uri uri) {
    if (_paymentDetected || !mounted) return;
    _paymentDetected = true;

    final txRef = uri.queryParameters['tx_ref'] ?? widget.txRef;

    // Navigate to payment status screen which handles verification
    context.pushReplacement(
      AppRoutes.paymentStatus,
      extra: {
        'booking': widget.booking,
        'tx_ref': txRef,
      },
    );
  }

  void _handleBack() {
    if (_paymentDetected) return;

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Cancel Payment?'),
        content: const Text(
            'Are you sure you want to go back? Your payment has not been completed.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Stay'),
          ),
          TextButton(
            onPressed: () {
              Navigator.pop(ctx);
              Navigator.pop(context);
            },
            child: const Text('Go Back',
                style: TextStyle(color: AppColors.error)),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) _handleBack();
      },
      child: Scaffold(
        backgroundColor: AppColors.background,
        appBar: AppBar(
          title: const Text('Chapa Payment'),
          leading: IconButton(
            icon: const Icon(LucideIcons.arrowLeft),
            onPressed: _handleBack,
          ),
          actions: [
            if (_isLoading)
              const Padding(
                padding: EdgeInsets.all(16.0),
                child: SizedBox(
                  width: 20,
                  height: 20,
                  child: CircularProgressIndicator(strokeWidth: 2),
                ),
              ),
          ],
        ),
        body: Stack(
          children: [
            WebViewWidget(controller: _controller),

            // Loading indicator
            if (_isLoading)
              const Center(
                child: CircularProgressIndicator(color: AppColors.primary),
              ),

            // Error overlay
            if (_errorMessage != null)
              Center(
                child: Padding(
                  padding: const EdgeInsets.all(24),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(LucideIcons.alertCircle,
                          size: 48, color: AppColors.error),
                      const SizedBox(height: 16),
                      Text(
                        _errorMessage!,
                        textAlign: TextAlign.center,
                        style: const TextStyle(fontSize: 16),
                      ),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: () => Navigator.pop(context),
                        child: const Text('Go Back'),
                      ),
                    ],
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
