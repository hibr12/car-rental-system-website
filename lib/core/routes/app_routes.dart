import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../core/config/auth_state.dart';
import '../../screens/splash/splash_screen.dart';
import '../../screens/onboarding/onboarding_screen.dart';
import '../../screens/auth/login_screen.dart';
import '../../screens/auth/register_screen.dart';
import '../../screens/auth/forgot_password_screen.dart';
import '../../screens/main_shell.dart';
import '../../screens/browse/browse_screen.dart';
import '../../screens/vehicle/vehicle_details_screen.dart';
import '../../screens/booking/booking_date_screen.dart';
import '../../screens/booking/booking_summary_screen.dart';
import '../../screens/booking/booking_success_screen.dart';
import '../../screens/favorites/favorites_screen.dart';
import '../../screens/notifications/notifications_screen.dart';
import '../../screens/reviews/reviews_screen.dart';
import '../../screens/profile/settings_screen.dart';
import '../../screens/profile/legal_screen.dart';
import '../../screens/profile/rewards_screen.dart';
import '../../screens/support/support_screen.dart';
import '../../screens/support/chat_screen.dart';
import '../../screens/profile/edit_profile_screen.dart';
import '../../screens/profile/payment_methods_screen.dart';
import '../../screens/profile/add_payment_method_screen.dart';
import '../../screens/profile/driver_license_screen.dart';
import '../../screens/profile/change_password_screen.dart';
import '../../screens/profile/saved_addresses_screen.dart';
import '../../screens/legal/rental_agreement_screen.dart';
import '../../screens/legal/insurance_policy_screen.dart';
import '../../screens/legal/cancellation_policy_screen.dart';
import '../../screens/legal/driver_requirements_screen.dart';
import '../../screens/reservations/reservation_details_screen.dart';
import '../../screens/reservations/cancel_reservation_screen.dart';
import '../../screens/reservations/write_review_screen.dart';
import '../../screens/reservations/inspection/vehicle_inspection_screen.dart';
import '../../screens/reservations/inspection/inspection_summary_screen.dart';
import '../../screens/booking/extras_screen.dart';
import '../../screens/branches/branch_list_screen.dart';
import '../../screens/branches/branch_detail_screen.dart';
import '../../screens/transactions/transaction_history_screen.dart';
import '../../screens/transactions/invoice_detail_screen.dart';
import '../../models/vehicle_model.dart' as vehicle_model;
import '../../models/booking_model.dart' as booking_model;
import '../../models/booking_draft.dart' as booking_draft;
import '../../models/branch_model.dart' as branch_model;
import '../../models/transaction_model.dart' as transaction_model;

class AppRoutes {
  AppRoutes._();

  // ── Public routes (no auth required) ───────────────────────────────
  static const String splash = '/';
  static const String onboarding = '/onboarding';
  static const String login = '/login';
  static const String register = '/register';
  static const String forgotPassword = '/forgot-password';

  // ── Protected routes (require auth token) ──────────────────────────
  static const String home = '/home';
  static const String browse = '/browse';
  static const String vehicleDetails = '/vehicle-details';
  static const String bookingDate = '/booking-date';
  static const String bookingSummary = '/booking-summary';
  static const String bookingSuccess = '/booking-success';
  static const String favorites = '/favorites';
  static const String notifications = '/notifications';
  static const String reviews = '/reviews';
  static const String settings = '/settings';
  static const String legal = '/legal';
  static const String rewards = '/rewards';
  static const String support = '/support';
  static const String chat = '/chat';
  static const String editProfile = '/edit-profile';
  static const String paymentMethods = '/payment-methods';
  static const String addPaymentMethod = '/add-payment-method';
  static const String driverLicense = '/profile/license';
  static const String changePassword = '/change-password';
  static const String savedAddresses = '/saved-addresses';
  static const String rentalAgreement = '/rental-agreement';
  static const String insurancePolicy = '/insurance-policy';
  static const String cancellationPolicy = '/cancellation-policy';
  static const String driverRequirements = '/driver-requirements';
  static const String reservationDetails = '/reservation-details';
  static const String cancelReservation = '/cancel-reservation';
  static const String writeReview = '/write-review';
  static const String extras = '/extras';
  static const String branchList = '/branch-list';
  static const String branchDetail = '/branch-detail';
  static const String transactionHistory = '/transaction-history';
  static const String invoiceDetail = '/invoice-detail';
  static const String vehicleInspection = '/inspection';
  static const String inspectionSummary = '/inspection-summary';

  /// All paths that require an active session.
  static const _protectedPaths = [
    home,
    browse,
    favorites,
    notifications,
    reviews,
    settings,
    legal,
    rewards,
    support,
    chat,
    editProfile,
    paymentMethods,
    addPaymentMethod,
    driverLicense,
    changePassword,
    savedAddresses,
    rentalAgreement,
    insurancePolicy,
    cancellationPolicy,
    driverRequirements,
    reservationDetails,
    cancelReservation,
    writeReview,
    extras,
    branchList,
    branchDetail,
    transactionHistory,
    invoiceDetail,
    vehicleInspection,
    inspectionSummary,
    vehicleDetails,
    bookingDate,
    bookingSummary,
    bookingSuccess,
  ];

  // ── Router ────────────────────────────────────────────────────────

  static final GoRouter router = GoRouter(
    initialLocation: splash,
    redirect: (context, state) {
      final isLoggedIn = AuthState.isAuthenticated;
      final path = state.matchedLocation;

      // Splash always passes through — it decides its own navigation.
      if (path == splash) return null;

      // Public auth pages should redirect to home if already logged in.
      if (isLoggedIn &&
          (path == login || path == register || path == onboarding)) {
        return home;
      }

      // Protected paths redirect to login if not authenticated.
      if (!isLoggedIn && _protectedPaths.contains(path)) {
        return login;
      }

      return null;
    },
    routes: [
      GoRoute(path: splash, builder: (_, __) => const SplashScreen()),
      GoRoute(path: onboarding, builder: (_, __) => const OnboardingScreen()),
      GoRoute(path: login, builder: (_, __) => const LoginScreen()),
      GoRoute(path: register, builder: (_, __) => const RegisterScreen()),
      GoRoute(
          path: forgotPassword,
          builder: (_, __) => const ForgotPasswordScreen()),
      GoRoute(path: home, builder: (_, __) => const MainShell()),
      GoRoute(
        path: browse,
        builder: (context, state) {
          // Optional initial category slug passed from the home chips.
          final slug = state.extra is String ? state.extra as String : null;
          return BrowseScreen(initialCategorySlug: slug);
        },
      ),
      GoRoute(
        path: vehicleDetails,
        builder: (context, state) {
          final vehicle = _extra<vehicle_model.Vehicle>(context, state);
          return VehicleDetailsScreen(vehicle: vehicle);
        },
      ),
      GoRoute(
        path: bookingDate,
        builder: (context, state) {
          final vehicle = _extra<vehicle_model.Vehicle>(context, state);
          return BookingDateScreen(vehicle: vehicle);
        },
      ),
      GoRoute(
        path: bookingSummary,
        builder: (context, state) {
          // Accepts a BookingDraft (preferred) or a bare Vehicle (fallback).
          final extra = state.extra;
          if (extra is booking_draft.BookingDraft) {
            return BookingSummaryScreen(draft: extra);
          }
          if (extra is vehicle_model.Vehicle) {
            return BookingSummaryScreen(
              draft: booking_draft.BookingDraft(
                vehicle: extra,
                pickupDate: DateTime.now().add(const Duration(days: 1)),
                returnDate: DateTime.now().add(const Duration(days: 3)),
                pickupTime: const TimeOfDay(hour: 10, minute: 0),
                returnTime: const TimeOfDay(hour: 10, minute: 0),
                pickupLocation: extra.location,
                returnLocation: extra.location,
              ),
            );
          }
          // Wrong/missing extra — pop back instead of crashing.
          return _fallbackPop(
              context,
              const Scaffold(
                  body: Center(child: Text('Invalid booking data'))));
        },
      ),
      GoRoute(
        path: bookingSuccess,
        builder: (context, state) {
          // Prefer a real Booking (carries the booking_reference).
          final extra = state.extra;
          if (extra is booking_model.Booking) {
            return BookingSuccessScreen(booking: extra);
          }
          if (extra is vehicle_model.Vehicle) {
            return BookingSuccessScreen(vehicle: extra);
          }
          return _fallbackPop(
            context,
            const Scaffold(
                body: Center(child: Text('Booking data unavailable'))),
          );
        },
      ),
      GoRoute(path: favorites, builder: (_, __) => const FavoritesScreen()),
      GoRoute(
          path: notifications, builder: (_, __) => const NotificationsScreen()),
      GoRoute(
        path: reviews,
        builder: (context, state) {
          final vehicle = _extra<vehicle_model.Vehicle>(context, state);
          return ReviewsScreen(vehicle: vehicle);
        },
      ),
      GoRoute(path: settings, builder: (_, __) => const SettingsScreen()),
      GoRoute(path: legal, builder: (_, __) => const LegalScreen()),
      GoRoute(path: rewards, builder: (_, __) => const RewardsScreen()),
      GoRoute(path: support, builder: (_, __) => const SupportScreen()),
      GoRoute(path: chat, builder: (_, __) => const ChatScreen()),
      GoRoute(
        path: reservationDetails,
        builder: (context, state) {
          final booking = _extra<booking_model.Booking>(context, state);
          return ReservationDetailsScreen(booking: booking);
        },
      ),
      GoRoute(
        path: cancelReservation,
        builder: (context, state) {
          final booking = _extra<booking_model.Booking>(context, state);
          return CancelReservationScreen(booking: booking);
        },
      ),
      GoRoute(
        path: writeReview,
        builder: (context, state) {
          final booking = _extra<booking_model.Booking>(context, state);
          return WriteReviewScreen(booking: booking);
        },
      ),
      GoRoute(
        path: extras,
        builder: (context, state) {
          final extra = state.extra;
          if (extra is booking_draft.BookingDraft) {
            return ExtrasScreen(vehicle: extra.vehicle, draft: extra);
          }
          final vehicle = _extra<vehicle_model.Vehicle>(context, state);
          return ExtrasScreen(vehicle: vehicle);
        },
      ),
      GoRoute(path: branchList, builder: (_, __) => const BranchListScreen()),
      GoRoute(
        path: branchDetail,
        builder: (context, state) {
          final branch = _extra<branch_model.Branch>(context, state);
          return BranchDetailScreen(branch: branch);
        },
      ),
      GoRoute(
          path: transactionHistory,
          builder: (_, __) => const TransactionHistoryScreen()),
      GoRoute(
        path: invoiceDetail,
        builder: (context, state) {
          final txn = _extra<transaction_model.Transaction>(context, state);
          return InvoiceDetailScreen(transaction: txn);
        },
      ),
      GoRoute(path: editProfile, builder: (_, __) => const EditProfileScreen()),
      GoRoute(
          path: paymentMethods,
          builder: (_, __) => const PaymentMethodsScreen()),
      GoRoute(
          path: addPaymentMethod,
          builder: (_, __) => const AddPaymentMethodScreen()),
      GoRoute(
          path: driverLicense, builder: (_, __) => const DriverLicenseScreen()),
      GoRoute(
          path: changePassword,
          builder: (_, __) => const ChangePasswordScreen()),
      GoRoute(
          path: savedAddresses,
          builder: (_, __) => const SavedAddressesScreen()),
      GoRoute(
          path: rentalAgreement,
          builder: (_, __) => const RentalAgreementScreen()),
      GoRoute(
          path: insurancePolicy,
          builder: (_, __) => const InsurancePolicyScreen()),
      GoRoute(
          path: cancellationPolicy,
          builder: (_, __) => const CancellationPolicyScreen()),
      GoRoute(
          path: driverRequirements,
          builder: (_, __) => const DriverRequirementsScreen()),
      GoRoute(
        path: vehicleInspection,
        builder: (context, state) {
          final isReturn = state.extra as bool? ?? false;
          return VehicleInspectionScreen(isReturn: isReturn);
        },
      ),
      GoRoute(
        path: inspectionSummary,
        builder: (context, state) {
          final isReturn = state.extra as bool? ?? false;
          return InspectionSummaryScreen(isReturn: isReturn);
        },
      ),
    ],
  );
}

/// Safely extracts a typed extra from route state.
///
/// If the extra is missing or the wrong type, pops the route (which
/// falls back to the previous screen) instead of throwing a [TypeError]
/// that would crash the app.
T _extra<T>(BuildContext context, GoRouterState state) {
  final extra = state.extra;
  if (extra is T) return extra;
  // Fallback: pop back so the user never sees a red error screen.
  WidgetsBinding.instance.addPostFrameCallback((_) {
    if (context.mounted) Navigator.of(context).pop();
  });
  // This line is technically unreachable for correct navigation,
  // but Dart's type system needs a return. Returning a dummy via
  // a late error is safer than throwing here.
  throw StateError('Route extra was missing or wrong type (expected $T).');
}

/// Returns [screen] but schedules a [Navigator.maybePop] for the next frame
/// so the user is sent back when a route was opened with a missing/invalid
/// extra, instead of seeing a red error screen.
Widget _fallbackPop(BuildContext context, Widget screen) {
  WidgetsBinding.instance.addPostFrameCallback((_) {
    if (context.mounted) Navigator.of(context).maybePop();
  });
  return screen;
}
