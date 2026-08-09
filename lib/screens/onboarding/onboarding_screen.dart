import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:smooth_page_indicator/smooth_page_indicator.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../core/routes/app_routes.dart';
import '../../widgets/buttons/app_buttons.dart';

/// Onboarding flow shown on first launch.
///
/// [onComplete] is invoked when the user finishes or skips the flow —
/// wire this up in the caller to persist a "has_seen_onboarding" flag
/// (e.g. via SharedPreferences) before navigating, rather than doing
/// persistence inside this widget.
class OnboardingScreen extends StatefulWidget {
  final VoidCallback? onComplete;

  const OnboardingScreen({super.key, this.onComplete});

  @override
  State<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends State<OnboardingScreen> {
  late final PageController _pageController;
  double _page = 0;
  int get _currentPage => _page.round();

  static const _transitionDuration = Duration(milliseconds: 350);
  static const _transitionCurve = Curves.easeOutCubic;

  final List<_OnboardingPage> _pages = const [
    _OnboardingPage(
      title: 'Find your perfect ride',
      description:
          'Browse thousands of verified vehicles near you and book the one that fits your trip.',
      assetPath: 'assets/onboarding/find_ride.png',
      fallbackIcon: Icons.search_rounded,
      backgroundColor: Color(0xFFE8ECFB),
      accentColor: Color(0xFF4F46E5),
    ),
    _OnboardingPage(
      title: 'Book with confidence',
      description:
          'Free cancellation up to 24 hours before pickup, plus insurance included on every booking.',
      assetPath: 'assets/onboarding/book_confidence.png',
      fallbackIcon: Icons.verified_user_rounded,
      backgroundColor: Color(0xFFE1F5EA),
      accentColor: Color(0xFF16A34A),
    ),
    _OnboardingPage(
      title: 'Unlock and go',
      description:
          'Skip the counter. Unlock your car from the app and hit the road in seconds.',
      assetPath: 'assets/onboarding/unlock_go.png',
      fallbackIcon: Icons.directions_car_filled_rounded,
      backgroundColor: Color(0xFFFDF3D9),
      accentColor: Color(0xFFD97706),
    ),
  ];

  @override
  void initState() {
    super.initState();
    _pageController = PageController()..addListener(_onScroll);
  }

  void _onScroll() {
    if (!_pageController.hasClients) return;
    setState(() =>
        _page = _pageController.page ?? _pageController.initialPage.toDouble());
  }

  @override
  void dispose() {
    _pageController.removeListener(_onScroll);
    _pageController.dispose();
    super.dispose();
  }

  void _finish() {
    widget.onComplete?.call();
    context.go(AppRoutes.login);
  }

  void _next() {
    if (_currentPage == _pages.length - 1) {
      _finish();
    } else {
      _pageController.nextPage(
          duration: _transitionDuration, curve: _transitionCurve);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isLastPage = _currentPage == _pages.length - 1;
    final size = MediaQuery.sizeOf(context);
    final illustrationSize = (size.width * 0.62).clamp(200.0, 300.0);

    return Scaffold(
      backgroundColor: AppColors.background,
      body: SafeArea(
        child: Column(
          children: [
            SizedBox(
              height: 48,
              child: Align(
                alignment: AlignmentDirectional.topEnd,
                child: AnimatedOpacity(
                  duration: const Duration(milliseconds: 200),
                  opacity: isLastPage ? 0 : 1,
                  child: IgnorePointer(
                    ignoring: isLastPage,
                    child: TextButton(
                      onPressed: _finish,
                      child: Text(
                        'Skip',
                        style: AppTypography.textTheme.labelLarge?.copyWith(
                          color: AppColors.textSecondary,
                        ),
                      ),
                    ),
                  ),
                ),
              ),
            ),
            Expanded(
              child: PageView.builder(
                controller: _pageController,
                physics: const ClampingScrollPhysics(),
                itemCount: _pages.length,
                itemBuilder: (context, index) {
                  final page = _pages[index];
                  // Parallax/fade driven by scroll offset for a less
                  // mechanical transition than a flat page snap.
                  final delta = (_page - index).clamp(-1.0, 1.0);
                  final opacity = (1 - delta.abs()).clamp(0.0, 1.0);
                  final slide = delta * 24;

                  return Padding(
                    padding:
                        const EdgeInsets.symmetric(horizontal: AppSpacing.xxl),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Transform.translate(
                          offset: Offset(slide, 0),
                          child: Opacity(
                            opacity: opacity,
                            child: Semantics(
                              label: page.title,
                              image: true,
                              child: _Illustration(
                                page: page,
                                size: illustrationSize,
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(height: AppSpacing.xxl),
                        Opacity(
                          opacity: opacity,
                          child: Text(
                            page.title,
                            style: AppTypography.textTheme.displaySmall,
                            textAlign: TextAlign.center,
                          ),
                        ),
                        const SizedBox(height: AppSpacing.md),
                        Opacity(
                          opacity: opacity,
                          child: Text(
                            page.description,
                            style: AppTypography.textTheme.bodyLarge?.copyWith(
                              color: AppColors.textSecondary,
                            ),
                            textAlign: TextAlign.center,
                          ),
                        ),
                      ],
                    ),
                  );
                },
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(AppSpacing.pagePadding),
              child: Column(
                children: [
                  SmoothPageIndicator(
                    controller: _pageController,
                    count: _pages.length,
                    effect: ExpandingDotsEffect(
                      activeDotColor: AppColors.primary,
                      dotColor: AppColors.border,
                      dotHeight: 8,
                      dotWidth: 8,
                      expansionFactor: 4,
                    ),
                  ),
                  const SizedBox(height: AppSpacing.xl),
                  PrimaryButton(
                    text: isLastPage ? 'Get Started' : 'Next',
                    onPressed: _next,
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

/// Renders the page illustration. Tries the brand asset first; if it's
/// missing (e.g. not yet dropped into pubspec assets during development),
/// falls back to a styled icon instead of throwing, so a missing asset
/// never blocks a build.
class _Illustration extends StatelessWidget {
  final _OnboardingPage page;
  final double size;

  const _Illustration({required this.page, required this.size});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: page.backgroundColor,
        shape: BoxShape.circle,
      ),
      child: ClipOval(
        child: Image.asset(
          page.assetPath,
          width: size,
          height: size,
          fit: BoxFit.contain,
          errorBuilder: (context, error, stackTrace) => Icon(
            page.fallbackIcon,
            size: size * 0.42,
            color: page.accentColor,
          ),
        ),
      ),
    );
  }
}

class _OnboardingPage {
  final String title;
  final String description;
  final String assetPath;
  final IconData fallbackIcon;
  final Color backgroundColor;
  final Color accentColor;

  const _OnboardingPage({
    required this.title,
    required this.description,
    required this.assetPath,
    required this.fallbackIcon,
    required this.backgroundColor,
    required this.accentColor,
  });
}
