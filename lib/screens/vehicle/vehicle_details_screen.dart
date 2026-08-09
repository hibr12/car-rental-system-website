import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:smooth_page_indicator/smooth_page_indicator.dart';
import 'package:go_router/go_router.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../models/vehicle_model.dart';
import '../../widgets/buttons/app_buttons.dart';
import '../../data/local/local_storage_service.dart';
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

  @override
  void initState() {
    super.initState();
    _checkFavoriteStatus();
  }

  Future<void> _checkFavoriteStatus() async {
    final isFav =
        await LocalStorageService.instance.isFavorite(widget.vehicle.id);
    if (mounted) {
      setState(() => _isFavorite = isFav);
    }
  }

  Future<void> _toggleFavorite() async {
    final currentStatus = _isFavorite;
    setState(() => _isFavorite = !currentStatus);

    if (currentStatus) {
      await LocalStorageService.instance.removeFavorite(widget.vehicle.id);
    } else {
      await LocalStorageService.instance.addFavorite(widget.vehicle.id);
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
      body: CustomScrollView(
        slivers: [
          _buildSliverAppBar(context),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(AppSpacing.pagePadding),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  VehicleTitleSection(vehicle: widget.vehicle),
                  const SizedBox(height: AppSpacing.xxl),
                  VehicleSpecificationsSection(vehicle: widget.vehicle),
                  const SizedBox(height: AppSpacing.xxl),
                  VehicleDescriptionSection(vehicle: widget.vehicle),
                  const SizedBox(height: AppSpacing.xxl),
                  VehicleFeaturesSection(vehicle: widget.vehicle),
                  const SizedBox(height: 100), // padding for bottom bar
                ],
              ),
            ),
          ),
        ],
      ),
      bottomSheet: VehicleBottomBar(vehicle: widget.vehicle),
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
            icon: _isFavorite ? LucideIcons.heart : LucideIcons.heart,
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
              itemCount: widget.vehicle.imageUrls.length,
              itemBuilder: (context, index) {
                return Hero(
                  tag: 'vehicle_image_${widget.vehicle.id}',
                  child: CachedNetworkImage(
                    imageUrl: widget.vehicle.imageUrls[index],
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
                  count: widget.vehicle.imageUrls.length,
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
