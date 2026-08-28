import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:smooth_page_indicator/smooth_page_indicator.dart';
import 'package:go_router/go_router.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../models/vehicle_model.dart';
import '../../widgets/buttons/app_buttons.dart';
import '../../data/local/local_storage_service.dart';
import '../../data/repositories/vehicle_repository.dart';
import '../../data/repositories/review_repository.dart';
import 'components/vehicle_details_components.dart';

class VehicleDetailsScreen extends StatefulWidget {
  final Vehicle vehicle;

  const VehicleDetailsScreen({super.key, required this.vehicle});

  @override
  State<VehicleDetailsScreen> createState() => _VehicleDetailsScreenState();
}

class _VehicleDetailsScreenState extends State<VehicleDetailsScreen> {
  final PageController _pageController = PageController();
  bool _isFavorite = false;

  /// Fresh copy re-fetched from `GET /vehicles/{id}` so availability and
  /// pricing are current — the passed-in object may be stale.
  late Vehicle _vehicle;
  bool _isRefreshing = false;
  String? _loadError;

  @override
  void initState() {
    super.initState();
    _vehicle = widget.vehicle;
    _checkFavoriteStatus();
    _refreshVehicle();
  }

  Future<void> _checkFavoriteStatus() async {
    final isFav =
        await LocalStorageService.instance.isFavorite(widget.vehicle.id);
    if (mounted) {
      setState(() => _isFavorite = isFav);
    }
  }

  /// Re-fetches the vehicle and enriches it with the public review average.
  /// Both calls are best-effort: on failure we keep showing what we have.
  Future<void> _refreshVehicle() async {
    setState(() {
      _isRefreshing = true;
      _loadError = null;
    });

    Vehicle refreshed = widget.vehicle;

    final res = await VehicleRepository.instance
        .getVehicleById(widget.vehicle.id);
    if (res.success && res.data != null) {
      refreshed = res.data!;
    } else if (!res.success &&
        res.error != null &&
        res.error!.statusCode == 404) {
      if (mounted) {
        setState(() {
          _isRefreshing = false;
          _loadError = 'This vehicle is no longer available.';
        });
      }
      return;
    }

    // Public reviews meta carries `average_rating` — the only aggregate the
    // backend exposes for customers.
    final reviewsRes =
        await ReviewRepository.instance.getVehicleReviews(widget.vehicle.id);
    if (reviewsRes.success && reviewsRes.data != null) {
      final average =
          (reviewsRes.data!.meta['average_rating'] as num?)?.toDouble() ?? 0;
      refreshed =
          refreshed.withRating(average, reviewsRes.data!.total);
    }

    if (mounted) {
      setState(() {
        _vehicle = refreshed;
        _isRefreshing = false;
      });
    }
  }

  Future<void> _toggleFavorite() async {
    final currentStatus = _isFavorite;
    setState(() => _isFavorite = !currentStatus);

    if (currentStatus) {
      await LocalStorageService.instance.removeFavorite(_vehicle.id);
    } else {
      await LocalStorageService.instance.addFavorite(_vehicle.id);
    }

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
              !currentStatus ? 'Added to favorites' : 'Removed from favorites'),
          duration: const Duration(seconds: 2),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      body: _loadError != null ? _buildUnavailable() : _buildContent(),
      bottomSheet:
          _loadError != null ? null : VehicleBottomBar(vehicle: _vehicle),
    );
  }

  Widget _buildUnavailable() {
    return SafeArea(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(LucideIcons.car, size: 56, color: AppColors.textTertiary),
          const SizedBox(height: AppSpacing.md),
          Text('Vehicle unavailable',
              style: AppTypography.textTheme.headlineMedium),
          const SizedBox(height: AppSpacing.sm),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: AppSpacing.xxl),
            child: Text(
              _loadError!,
              textAlign: TextAlign.center,
              style: AppTypography.textTheme.bodyMedium
                  ?.copyWith(color: AppColors.textSecondary),
            ),
          ),
          const SizedBox(height: AppSpacing.xl),
          PrimaryButton(text: 'Go Back', onPressed: () => context.pop()),
        ],
      ),
    );
  }

  Widget _buildContent() {
    return CustomScrollView(
      slivers: [
        _buildSliverAppBar(context),
        SliverToBoxAdapter(
          child: _isRefreshing
              ? LinearProgressIndicator(
                  minHeight: 2,
                  color: AppColors.primary.withOpacity(0.4),
                  backgroundColor: Colors.transparent,
                )
              : const SizedBox.shrink(),
        ),
        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsets.all(AppSpacing.pagePadding),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                VehicleTitleSection(vehicle: _vehicle),
                const SizedBox(height: AppSpacing.xxl),
                VehicleSpecificationsSection(vehicle: _vehicle),
                const SizedBox(height: AppSpacing.xxl),
                VehicleDescriptionSection(vehicle: _vehicle),
                // The backend provides no features array — render the section
                // only when real data exists.
                if (_vehicle.features.isNotEmpty) ...[
                  const SizedBox(height: AppSpacing.xxl),
                  VehicleFeaturesSection(vehicle: _vehicle),
                ],
                const SizedBox(height: 100), // padding for bottom bar
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildSliverAppBar(BuildContext context) {
    return SliverAppBar(
      expandedHeight: 300.0,
      pinned: true,
      backgroundColor: AppColors.surface,
      leading: Padding(
        padding: const EdgeInsets.all(AppSpacing.sm),
        child: AppIconButton(
          icon: LucideIcons.arrowLeft,
          onPressed: () => context.pop(),
        ),
      ),
      actions: [
        Padding(
          padding: const EdgeInsets.all(AppSpacing.sm),
          child: AppIconButton(
            icon: _isFavorite ? Icons.favorite : LucideIcons.heart,
            iconColor: _isFavorite ? AppColors.error : AppColors.textPrimary,
            onPressed: _toggleFavorite,
          ),
        ),
      ],
      flexibleSpace: FlexibleSpaceBar(
        background: Stack(
          children: [
            PageView.builder(
              controller: _pageController,
              itemCount: _vehicle.imageUrls.length,
              itemBuilder: (context, index) {
                return Hero(
                  tag: 'vehicle_image_${_vehicle.id}',
                  child: CachedNetworkImage(
                    imageUrl: _vehicle.imageUrls[index],
                    fit: BoxFit.cover,
                    errorWidget: (context, url, error) => Container(
                      color: AppColors.textTertiary,
                      child: const Center(
                        child: Icon(LucideIcons.imageOff,
                            size: 48, color: AppColors.surface),
                      ),
                    ),
                  ),
                );
              },
            ),
            Positioned(
              bottom: AppSpacing.md,
              left: 0,
              right: 0,
              child: Center(
                child: SmoothPageIndicator(
                  controller: _pageController,
                  count: _vehicle.imageUrls.length,
                  effect: const WormEffect(
                    dotHeight: 8,
                    dotWidth: 8,
                    activeDotColor: AppColors.surface,
                    dotColor: AppColors.textTertiary,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
