import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:mobile/core/spacing/app_spacing.dart';
import 'package:go_router/go_router.dart';
import '../../core/colors/app_colors.dart';
import '../../core/typography/app_typography.dart';
import '../../core/routes/app_routes.dart';
import '../../core/config/auth_state.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;
  late final Animation<double> _fadeAnimation;
  late final Animation<double> _scaleAnimation;

  // Floor on how long the splash stays up, so a fast device/network
  // doesn't flash the logo for 80ms — but real init work (below) is what
  // actually gates navigation, not an arbitrary timer.
  static const _minimumDisplay = Duration(milliseconds: 1200);

  bool _navigated = false;

  @override
  void initState() {
    super.initState();

    SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(
      statusBarColor: Colors.transparent,
      statusBarIconBrightness: Brightness.light,
      statusBarBrightness: Brightness.dark,
    ));

    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1500),
    );

    _fadeAnimation = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeIn),
    );

    _scaleAnimation = Tween<double>(begin: 0.8, end: 1.0).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeOutBack),
    );

    // Reduced-motion users get the logo immediately instead of a
    // half-second animated intro they didn't ask for.
    // Read this from the platform dispatcher rather than
    // MediaQuery.of(context) — the latter isn't safe to call in
    // initState() since the widget isn't finished mounting yet.
    final disableAnimations = WidgetsBinding
        .instance.platformDispatcher.accessibilityFeatures.disableAnimations;
    if (disableAnimations) {
      _controller.value = 1.0;
    } else {
      _controller.forward();
    }

    _bootstrap();
  }

  Future<void> _bootstrap() async {
    // Run the minimum display timer and any real startup work
    // (session/token validation, remote config, etc.) concurrently so
    // slow network calls don't add on top of the splash timer, and fast
    // ones don't cut the splash short.
    final results = await Future.wait([
      Future.delayed(_minimumDisplay),
      _resolveDestination(),
    ]);

    if (!mounted || _navigated) return;
    _navigated = true;

    final destination = results[1] as String;
    context.go(destination);
  }

  Future<String> _resolveDestination() async {
    // Auto-login: if a token was persisted, go straight to home.
    // The router's redirect guard handles the case where the token
    // has expired server-side (the first API call will 401 → clear
    // token → router redirects to login).
    try {
      final isAuthenticated = AuthState.isAuthenticated;
      return isAuthenticated ? AppRoutes.home : AppRoutes.onboarding;
    } catch (_) {
      // Fail closed: if we can't verify the session, send the user
      // through onboarding/login rather than risk landing on an
      // authenticated screen with no valid session.
      return AppRoutes.onboarding;
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.primary,
      body: Center(
        child: FadeTransition(
          opacity: _fadeAnimation,
          child: ScaleTransition(
            scale: _scaleAnimation,
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  padding: const EdgeInsets.all(AppSpacing.lg),
                  decoration: const BoxDecoration(
                    color: AppColors.surface,
                    shape: BoxShape.circle,
                  ),
                  child: ClipOval(
                    child: Image.asset(
                      'assets/brand/logo_mark.png',
                      width: 64,
                      height: 64,
                      fit: BoxFit.contain,
                      errorBuilder: (context, error, stackTrace) => const Icon(
                        Icons.directions_car,
                        size: 64,
                        color: AppColors.primary,
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: AppSpacing.lg),
                Text(
                  'DriveEase',
                  style: AppTypography.textTheme.displayMedium?.copyWith(
                    color: AppColors.surface,
                  ),
                ),
                const SizedBox(height: AppSpacing.sm),
                Text(
                  'Premium Car Rentals',
                  style: AppTypography.textTheme.bodyLarge?.copyWith(
                    color: AppColors.surface.withOpacity(0.75),
                    letterSpacing: 1.2,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
