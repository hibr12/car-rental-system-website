import 'package:flutter/material.dart';
import '../../core/colors/app_colors.dart';
import '../../core/typography/app_typography.dart';
import '../../core/spacing/app_spacing.dart';
import '../../widgets/cards/vehicle_card.dart';
import '../../widgets/cards/vehicle_card_skeleton.dart';
import 'package:go_router/go_router.dart';
import '../../core/routes/app_routes.dart';
import '../../models/vehicle_model.dart';

import '../../data/repositories/user_repository.dart';
import '../../data/repositories/vehicle_repository.dart';
import '../../models/category_model.dart';
import '../../models/user_model.dart';
import '../../data/models/api_models.dart';
import '../../widgets/states/error_state_widget.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  bool _isLoading = true;
  String? _errorMessage;
  User? _currentUser;
  List<Vehicle> _featuredCars = [];
  List<Vehicle> _popularCars = [];
  List<VehicleCategory> _categories = [];

  @override
  void initState() {
    super.initState();
    _fetchData();
  }

  Future<void> _fetchData() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

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

    if (!mounted) return;

    // Only hard-fail when the vehicle catalog itself is unreachable — the
    // user's name is cosmetic and categories may legitimately be empty.
    final hasVehicles =
        (featuredRes.success || popularRes.success) &&
            ((featuredRes.data?.isNotEmpty ?? false) ||
                (popularRes.data?.isNotEmpty ?? false));
    final failed = featuredRes.success == false && popularRes.success == false;

    setState(() {
      _currentUser = userRes.data;
      _featuredCars = featuredRes.data ?? [];
      _popularCars = popularRes.data ?? [];
      _categories = categoriesRes.data ?? [];
      _errorMessage = failed && !hasVehicles
          ? (featuredRes.error?.friendlyMessage ??
              popularRes.error?.friendlyMessage ??
              'Failed to load vehicles')
          : null;
      _isLoading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      body: SafeArea(
        child: _isLoading
            ? SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                child: _buildHomeSkeleton(),
              )
            : RefreshIndicator(
                onRefresh: _fetchData,
                child: _errorMessage != null
                    ? ListView(
                        physics: const AlwaysScrollableScrollPhysics(),
                        children: [
                          const SizedBox(height: 140),
                          ErrorStateWidget(
                            message: _errorMessage!,
                            onRetry: _fetchData,
                          ),
                        ],
                      )
                    : ListView(
                        physics: const AlwaysScrollableScrollPhysics(),
                        children: [
                          _buildHeader(),
                          _buildSearchBar(context),
                          if (_categories.isNotEmpty) _buildCategories(),
                          _buildSectionTitle('Featured Cars',
                              onSeeAll: () => context.push(AppRoutes.browse)),
                          _buildHorizontalCarList(_featuredCars,
                              emptyMessage:
                                  'No featured vehicles right now.\nBrowse all cars to find your ride.'),
                          _buildSectionTitle('Recently Added',
                              onSeeAll: () => context.push(AppRoutes.browse)),
                          _buildHorizontalCarList(_popularCars,
                              emptyMessage:
                                  'No vehicles available yet.\nPlease check back soon.'),
                          const SizedBox(height: AppSpacing.xxl),
                        ],
                      ),
              ),
      ),
    );
  }

  Widget _buildHeader() {
    final firstName =
        _currentUser?.fullName.split(' ').first.trim().isNotEmpty == true
            ? _currentUser!.fullName.split(' ').first
            : 'there';
    final profileImage = _currentUser?.profileImageUrl;
    final hour = DateTime.now().hour;
    final greeting =
        hour < 12 ? 'Good morning,' : (hour < 18 ? 'Good afternoon,' : 'Good evening,');

    return Padding(
      padding: const EdgeInsets.all(AppSpacing.pagePadding),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(greeting, style: AppTypography.textTheme.bodyLarge),
              Text(firstName, style: AppTypography.textTheme.displaySmall),
            ],
          ),
          CircleAvatar(
            radius: 24,
            backgroundImage:
                profileImage != null && profileImage.isNotEmpty
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
      child: TextField(
        readOnly: true,
        onTap: () => context.push(AppRoutes.browse),
        style: AppTypography.textTheme.bodyLarge,
        decoration: InputDecoration(
          hintText: 'Search cars, brands…',
          prefixIcon: const Icon(Icons.search, color: AppColors.textTertiary),
          filled: true,
          fillColor: AppColors.surface,
          contentPadding: const EdgeInsets.symmetric(vertical: AppSpacing.md),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
            borderSide: const BorderSide(color: AppColors.border),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
            borderSide: const BorderSide(color: AppColors.border),
          ),
        ),
      ),
    );
  }

  Widget _buildCategories() {
    // Real categories from the API only — no fabricated fallbacks.
    final categories = [
      (name: 'All', slug: '', icon: Icons.apps),
      ..._categories.map((c) => (
            name: c.name,
            slug: c.slug,
            icon: _iconFor(c.slug),
          )),
    ];

    return Container(
      height: 100,
      margin: const EdgeInsets.symmetric(vertical: AppSpacing.lg),
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: AppSpacing.pagePadding),
        itemCount: categories.length,
        itemBuilder: (context, index) {
          final cat = categories[index];

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
                      color: AppColors.surface,
                      borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
                      border: Border.all(color: AppColors.border),
                    ),
                    child: Icon(cat.icon, color: AppColors.textPrimary),
                  ),
                  const SizedBox(height: AppSpacing.sm),
                  Text(
                    cat.name,
                    style: AppTypography.textTheme.labelLarge?.copyWith(
                      color: AppColors.textSecondary,
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
      case 'sports':
        return Icons.local_taxi;
      case 'van':
      case 'minibus':
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
          Text(title, style: AppTypography.textTheme.headlineMedium),
          TextButton(
            onPressed: onSeeAll,
            child: Text('See All', style: AppTypography.textTheme.labelLarge),
          ),
        ],
      ),
    );
  }

  Widget _buildHorizontalCarList(List<Vehicle> cars,
      {required String emptyMessage}) {
    if (cars.isEmpty) {
      return Padding(
        padding: const EdgeInsets.symmetric(horizontal: AppSpacing.pagePadding),
        child: Container(
          width: double.infinity,
          height: 200,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
            border: Border.all(color: AppColors.border),
          ),
          child: Text(
            emptyMessage,
            textAlign: TextAlign.center,
            style: AppTypography.textTheme.bodyMedium
                ?.copyWith(color: AppColors.textSecondary),
          ),
        ),
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

  /// Horizontal strip of shimmer placeholders used while the home feed loads.
  Widget _buildHomeSkeleton() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.all(AppSpacing.pagePadding),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(width: 120, height: 14, color: AppColors.border),
                  const SizedBox(height: AppSpacing.sm),
                  Container(width: 90, height: 24, color: AppColors.border),
                ],
              ),
              const CircleAvatar(radius: 24, backgroundColor: AppColors.border),
            ],
          ),
        ),
        Padding(
          padding:
              const EdgeInsets.symmetric(horizontal: AppSpacing.pagePadding),
          child: Container(
            height: 52,
            decoration: BoxDecoration(
              color: AppColors.border,
              borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
            ),
          ),
        ),
        const SizedBox(height: AppSpacing.xl),
        SizedBox(
          height: 260,
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            padding:
                const EdgeInsets.symmetric(horizontal: AppSpacing.pagePadding),
            itemCount: 3,
            itemBuilder: (_, __) => const Padding(
              padding: EdgeInsets.only(right: AppSpacing.md),
              child: VehicleCardSkeleton(isHorizontal: false, width: 260),
            ),
          ),
        ),
      ],
    );
  }
}
