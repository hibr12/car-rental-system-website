import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:smooth_page_indicator/smooth_page_indicator.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../core/routes/app_routes.dart';
import '../../data/local/local_storage_service.dart';
import '../../widgets/buttons/app_buttons.dart';
import 'car_illustration.dart';

/// Onboarding flow shown on first launch. Persists the
/// `has_seen_onboarding` flag so it never replays for returning users.
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

  // Copy stays short and factual — every claim maps to a real Apex
  // Rentals capability (multi-branch fleet, date-based booking with live
  // availability, Chapa payment flow).
  final List<_OnboardingPage> _pages = const [
    _OnboardingPage(
      title: 'Discover your ride',
      description:
          'Explore our fleet across branches and find the right vehicle for your trip.',
      accent: AppColors.primary,
      badgeIcon: LucideIcons.badgeCheck,
      badgeLabel: 'Curated fleet',
    ),
    _OnboardingPage(
      title: 'Book with ease',
      description:
          'Choose your dates, check availability, and reserve your vehicle in minutes.',
      accent: AppColors.secondary,
      badgeIcon: LucideIcons.banknote,
      badgeLabel: 'Transparent ETB pricing',
    ),
    _OnboardingPage(
      title: 'Ready when you are',
      description:
          'Manage your reservation and pay securely through the supported payment system.',
      accent: AppColors.success,
      badgeIcon: LucideIcons.shieldCheck,
      badgeLabel: 'Secure checkout',
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
    // Persist first so the flow never replays, then continue.
    LocalStorageService.instance.setOnboardingSeen();
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

    // Scale the artwork with both axes so it never overflows on small or
    // unusually tall/short devices.
    final illustrationWidth = (size.width * 0.82).clamp(230.0, 330.0);
    final maxHeight = math.max(120.0, size.height * 0.34);
    final illustrationHeight = math.min(illustrationWidth * 0.6, maxHeight);

    return Scaffold(
      backgroundColor: AppColors.surface,
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

                  return Center(
                    child: SingleChildScrollView(
                      physics: const ClampingScrollPhysics(),
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
                                child: SizedBox(
                                  width: illustrationWidth,
                                  height: illustrationHeight,
                                  child: CarIllustration(
                                    accent: page.accent,
                                    badge: IllustrationBadge(
                                      icon: page.badgeIcon,
                                      label: page.badgeLabel,
                                    ),
                                  ),
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
                            child: ConstrainedBox(
                              constraints:
                                  const BoxConstraints(maxWidth: 320),
                              child: Text(
                                page.description,
                                style:
                                    AppTypography.textTheme.bodyLarge?.copyWith(
                                  color: AppColors.textSecondary,
                                  height: 1.5,
                                ),
                                textAlign: TextAlign.center,
                              ),
                            ),
                          ),
                        ],
                      ),
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
                    effect: const ExpandingDotsEffect(
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

class _OnboardingPage {
  final String title;
  final String description;
  final Color accent;
  final IconData badgeIcon;
  final String badgeLabel;

  const _OnboardingPage({
    required this.title,
    required this.description,
    required this.accent,
    required this.badgeIcon,
    required this.badgeLabel,
  });
}
