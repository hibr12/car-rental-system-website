import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/routes/app_routes.dart';
import '../../widgets/cards/vehicle_card.dart';
import '../../widgets/states/empty_state_widget.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../models/vehicle_model.dart';
import '../../data/local/local_storage_service.dart';
import '../../data/repositories/vehicle_repository.dart';

class FavoritesScreen extends StatefulWidget {
  const FavoritesScreen({super.key});

  @override
  State<FavoritesScreen> createState() => _FavoritesScreenState();
}

class _FavoritesScreenState extends State<FavoritesScreen> {
  bool _isLoading = true;
  List<Vehicle> _favorites = [];

  @override
  void initState() {
    super.initState();
    _loadFavorites();
  }

  Future<void> _loadFavorites() async {
    setState(() => _isLoading = true);

    final vehicleIds =
        await LocalStorageService.instance.getFavoriteVehicleIds();

    if (vehicleIds.isEmpty) {
      if (mounted) {
        setState(() {
          _favorites = [];
          _isLoading = false;
        });
      }
      return;
    }

    final List<Vehicle> loadedVehicles = [];
    final responses = await Future.wait(
        vehicleIds.map((id) => VehicleRepository.instance.getVehicleById(id)));

    for (final res in responses) {
      if (res.data != null) {
        loadedVehicles.add(res.data!);
      }
    }

    if (mounted) {
      setState(() {
        _favorites = loadedVehicles;
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Saved Vehicles'),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _favorites.isEmpty
              ? EmptyStateWidget(
                  icon: LucideIcons.heartOff,
                  title: 'No Saved Vehicles',
                  message: 'Vehicles you favorite will appear here.',
                  actionText: 'Browse Vehicles',
                  onAction: () => context.go(AppRoutes.home),
                )
              : RefreshIndicator(
                  onRefresh: _loadFavorites,
                  child: ListView.builder(
                    padding: const EdgeInsets.all(AppSpacing.pagePadding),
                    itemCount: _favorites.length,
                    itemBuilder: (context, index) {
                      return VehicleCard(
                        vehicle: _favorites[index],
                        isHorizontal: true,
                        onTap: () {
                          context
                              .push(AppRoutes.vehicleDetails,
                                  extra: _favorites[index])
                              .then((_) => _loadFavorites());
                        },
                      );
                    },
                  ),
                ),
    );
  }
}
