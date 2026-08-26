import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:mobile/core/spacing/app_spacing.dart';
import 'package:go_router/go_router.dart';
import '../../core/colors/app_colors.dart';
import '../../core/typography/app_typography.dart';
import '../../core/routes/app_routes.dart';
import '../../core/config/auth_state.dart';
import '../../data/local/local_storage_service.dart';

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
    final results = await Future.wait([
      Future.delayed(_minimumDisplay),
      _resolveDestination(),
    ]);

    if (!mounted || _navigated) return;
    _navigated = true;

    final destination = results[1] as String;
    if (mounted) context.go(destination);
  }

  Future<String> _resolveDestination() async {
    try {
      if (!AuthState.isAuthenticated) {
        final seenOnboarding =
            await LocalStorageService.instance.hasSeenOnboarding();
        return seenOnboarding ? AppRoutes.login : AppRoutes.onboarding;
      }

      // Validate the persisted token against the server before landing on
      // an authenticated screen. An expired/revoked token gets cleared so
      // the user starts at login instead of bouncing off a 401 later.
      final valid = await AuthState.validateSession();
      return valid ? AppRoutes.home : AppRoutes.login;
    } catch (_) {
      // Fail closed: if anything goes wrong, prefer the logged-out flow.
      await AuthState.clear();
      return AppRoutes.login;
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
                  child: const Icon(
                    Icons.directions_car,
                    size: 64,
                    color: AppColors.primary,
                  ),
                ),
                const SizedBox(height: AppSpacing.lg),
                Text(
                  'Apex Rentals',
                  style: AppTypography.textTheme.displayMedium?.copyWith(
                    color: AppColors.surface,
                  ),
                ),
                const SizedBox(height: AppSpacing.sm),
                Text(
                  'Drive Premium',
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
