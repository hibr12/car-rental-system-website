import 'package:flutter/material.dart';
import '../../core/colors/app_colors.dart';
import '../../core/typography/app_typography.dart';
import '../../core/spacing/app_spacing.dart';
import '../../widgets/inputs/app_text_field.dart';
import '../../widgets/cards/vehicle_card.dart';
import 'package:go_router/go_router.dart';
import '../../core/routes/app_routes.dart';
import '../../models/vehicle_model.dart';

import '../../data/repositories/user_repository.dart';
import '../../data/repositories/vehicle_repository.dart';
import '../../models/category_model.dart';
import '../../models/user_model.dart';
import '../../data/models/api_models.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  bool _isLoading = true;
  User? _currentUser;
  List<Vehicle> _featuredCars = [];
  List<Vehicle> _popularCars = [];
  List<VehicleCategory> _apiCategories = [];

  @override
  void initState() {
    super.initState();
    _fetchData();
  }

  Future<void> _fetchData() async {
    setState(() => _isLoading = true);

    final results = await Future.wait([
      UserRepository.instance.getCurrentUser(),
      VehicleRepository.instance.getFeaturedVehicles(),
      VehicleRepository.instance.getPopularVehicles(),
      VehicleRepository.instance.getCategories(),
    ]);

    final userRes = results[0] as ApiResponse<User>;
    final featuredRes = results[1] as ApiResponse<List<Vehicle>>;
    final popularRes = results[2] as ApiResponse<List<Vehicle>>;
    final categoriesRes = results[3] as ApiResponse<List<VehicleCategory>>;

    if (mounted) {
      setState(() {
        _currentUser = userRes.data;
        _featuredCars = featuredRes.data ?? [];
        _popularCars = popularRes.data ?? [];
        _apiCategories = categoriesRes.data ?? [];
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(
        backgroundColor: AppColors.background,
        body: Center(child: CircularProgressIndicator()),
      );
    }

    return Scaffold(
      backgroundColor: AppColors.background,
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: _fetchData,
          child: SingleChildScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _buildHeader(),
                _buildSearchBar(context),
                _buildCategories(),
                _buildSectionTitle('Featured Cars',
                    onSeeAll: () => context.push(AppRoutes.browse)),
                _buildHorizontalCarList(_featuredCars),
                _buildSectionTitle('Popular Rentals',
                    onSeeAll: () => context.push(AppRoutes.browse)),
                _buildHorizontalCarList(_popularCars),
                const SizedBox(height: AppSpacing.xxl),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildHeader() {
    final firstName = _currentUser?.fullName.split(' ').first ?? 'Guest';
    final profileImage = _currentUser?.profileImageUrl;

    return Padding(
      padding: const EdgeInsets.all(AppSpacing.pagePadding),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Good morning,',
                style: AppTypography.textTheme.bodyLarge,
              ),
              Text(
                firstName,
                style: AppTypography.textTheme.displaySmall,
              ),
            ],
          ),
          CircleAvatar(
            radius: 24,
            backgroundImage: profileImage != null && profileImage.isNotEmpty
                ? NetworkImage(profileImage)
                : null,
            child: profileImage == null || profileImage.isEmpty
                ? const Icon(Icons.person)
                : null,
          ),
        ],
      ),
    );
  }

  Widget _buildSearchBar(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: AppSpacing.pagePadding),
      child: SearchBarWidget(
        readOnly: true,
        onTap: () => context.push(AppRoutes.browse),
        onFilterTap: () => context.push(AppRoutes.browse),
      ),
    );
  }

  Widget _buildCategories() {
    // Real categories from the API, with sensible fallback icons per slug.
    // Falls back to a short hardcoded list when categories haven't loaded.
    final List<({String name, String slug, IconData icon})> categories;
    if (_apiCategories.isEmpty) {
      categories = [
        (name: 'All', slug: '', icon: Icons.apps),
        (name: 'Luxury', slug: 'luxury', icon: Icons.diamond),
        (name: 'SUV', slug: 'suv', icon: Icons.airport_shuttle),
        (name: 'Electric', slug: 'electric', icon: Icons.electric_car),
        (name: 'Economy', slug: 'economy', icon: Icons.directions_car),
      ];
    } else {
      categories = [
        (name: 'All', slug: '', icon: Icons.apps),
        ..._apiCategories.map((c) => (
              name: c.name,
              slug: c.slug,
              icon: _iconFor(c.slug),
            )),
      ];
    }

    return Container(
      height: 100,
      margin: const EdgeInsets.symmetric(vertical: AppSpacing.lg),
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: AppSpacing.pagePadding),
        itemCount: categories.length,
        itemBuilder: (context, index) {
          final cat = categories[index];
          final isSelected = index == 0;

          return GestureDetector(
            onTap: () => context.push(
              AppRoutes.browse,
              extra: cat.slug.isNotEmpty ? cat.slug : null,
            ),
            child: Padding(
              padding: const EdgeInsets.only(right: AppSpacing.md),
              child: Column(
                children: [
                  Container(
                    width: 60,
                    height: 60,
                    decoration: BoxDecoration(
                      color: isSelected ? AppColors.primary : AppColors.surface,
                      borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
                      border: isSelected
                          ? null
                          : Border.all(color: AppColors.border),
                    ),
                    child: Icon(
                      cat.icon,
                      color: isSelected
                          ? AppColors.surface
                          : AppColors.textPrimary,
                    ),
                  ),
                  const SizedBox(height: AppSpacing.sm),
                  Text(
                    cat.name,
                    style: AppTypography.textTheme.labelLarge?.copyWith(
                      color: isSelected
                          ? AppColors.primary
                          : AppColors.textSecondary,
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  /// Pick a representative icon for a category slug.
  IconData _iconFor(String slug) {
    switch (slug.toLowerCase()) {
      case 'luxury':
        return Icons.diamond;
      case 'suv':
        return Icons.airport_shuttle;
      case 'electric':
        return Icons.electric_car;
      case 'economy':
        return Icons.directions_car;
      case 'sedan':
        return Icons.directions_car;
      case 'sports':
        return Icons.local_taxi;
      case 'van':
        return Icons.airport_shuttle;
      default:
        return Icons.directions_car;
    }
  }

  Widget _buildSectionTitle(String title, {required VoidCallback onSeeAll}) {
    return Padding(
      padding: const EdgeInsets.symmetric(
        horizontal: AppSpacing.pagePadding,
        vertical: AppSpacing.md,
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            title,
            style: AppTypography.textTheme.headlineMedium,
          ),
          TextButton(
            onPressed: onSeeAll,
            child: Text('See All', style: AppTypography.textTheme.labelLarge),
          ),
        ],
      ),
    );
  }

  Widget _buildHorizontalCarList(List<Vehicle> cars) {
    if (cars.isEmpty) {
      return const SizedBox(
        height: 260,
        child: Center(child: Text('No vehicles found')),
      );
    }
    return SizedBox(
      height: 260,
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: AppSpacing.pagePadding),
        itemCount: cars.length,
        itemBuilder: (context, index) {
          return Padding(
            padding: const EdgeInsets.only(right: AppSpacing.md),
            child: VehicleCard(
              width: 260,
              vehicle: cars[index],
              onTap: () {
                context.push(AppRoutes.vehicleDetails, extra: cars[index]);
              },
            ),
          );
        },
      ),
    );
  }
}
