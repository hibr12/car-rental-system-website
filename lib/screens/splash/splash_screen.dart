import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
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
  late final Animation<double> _logoFade;
  late final Animation<double> _logoScale;
  late final Animation<double> _wordmarkFade;
  late final Animation<double> _wordmarkSlide;
  late final Animation<double> _taglineFade;

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
      duration: const Duration(milliseconds: 1100),
    );

    final curve = CurvedAnimation(parent: _controller, curve: Curves.easeOut);
    _logoFade =
        Tween(begin: 0.0, end: 1.0).animate(curve);
    _logoScale = Tween(begin: 0.86, end: 1.0).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeOutCubic),
    );
    _wordmarkFade = Tween(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(
        parent: _controller,
        curve: const Interval(0.3, 0.75, curve: Curves.easeOut),
      ),
    );
    _wordmarkSlide = Tween(begin: 10.0, end: 0.0).animate(
      CurvedAnimation(
        parent: _controller,
        curve: const Interval(0.3, 0.75, curve: Curves.easeOutCubic),
      ),
    );
    _taglineFade = Tween(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(
        parent: _controller,
        curve: const Interval(0.5, 1.0, curve: Curves.easeOut),
      ),
    );

    // Reduced-motion users get the brand immediately instead of an
    // animated intro they didn't ask for.
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
      body: DecoratedBox(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [AppColors.primaryDark, AppColors.primary],
          ),
        ),
        child: Stack(
          children: [
            // Soft glow behind the brand mark.
            Center(
              child: FadeTransition(
                opacity: _logoFade,
                child: Container(
                  width: 320,
                  height: 320,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    gradient: RadialGradient(
                      colors: [
                        AppColors.surface.withOpacity(0.14),
                        AppColors.surface.withOpacity(0.0),
                      ],
                    ),
                  ),
                ),
              ),
            ),
            Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  FadeTransition(
                    opacity: _logoFade,
                    child: ScaleTransition(
                      scale: _logoScale,
                      child: Container(
                        width: 96,
                        height: 96,
                        decoration: BoxDecoration(
                          color: AppColors.surface,
                          borderRadius:
                              BorderRadius.circular(AppSpacing.radiusXl),
                          boxShadow: [
                            BoxShadow(
                              color: AppColors.textPrimary.withOpacity(0.25),
                              blurRadius: 32,
                              offset: const Offset(0, 12),
                            ),
                          ],
                        ),
                        child: const Icon(
                          Icons.directions_car_rounded,
                          size: 52,
                          color: AppColors.primary,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: AppSpacing.xl),
                  FadeTransition(
                    opacity: _wordmarkFade,
                    child: SlideTransition(
                      position: Tween<Offset>(
                        begin: const Offset(0, 0.35),
                        end: Offset.zero,
                      ).animate(_wordmarkSlide),
                      child: Text.rich(
                        TextSpan(
                          text: 'Apex',
                          style:
                              AppTypography.textTheme.displayMedium?.copyWith(
                            color: AppColors.surface,
                          ),
                          children: [
                            TextSpan(
                              text: ' Rentals',
                              style: AppTypography.textTheme.displayMedium
                                  ?.copyWith(
                                color: AppColors.surface.withOpacity(0.82),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: AppSpacing.sm),
                  FadeTransition(
                    opacity: _taglineFade,
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: AppSpacing.md,
                        vertical: AppSpacing.xs,
                      ),
                      decoration: BoxDecoration(
                        border: Border.all(
                          color: AppColors.surface.withOpacity(0.3),
                        ),
                        borderRadius:
                            BorderRadius.circular(AppSpacing.radiusPill),
                      ),
                      child: Text(
                        'DRIVE PREMIUM',
                        style:
                            AppTypography.textTheme.labelSmall?.copyWith(
                          color: AppColors.surface.withOpacity(0.85),
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                          letterSpacing: 3.2,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
