import 'package:shared_preferences/shared_preferences.dart';

/// Device-local preferences that have no backend representation.
///
/// Only two concerns live here: the local vehicle wishlist (favorites are a
/// per-device convenience — the backend has no favorites API) and the
/// onboarding-seen flag.
class LocalStorageService {
  static const String _favoritesKey = 'favorite_vehicle_ids';
  static const String _onboardingKey = 'has_seen_onboarding';

  static final LocalStorageService instance = LocalStorageService._internal();

  LocalStorageService._internal();

  /// Favorites

  Future<List<String>> getFavoriteVehicleIds() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getStringList(_favoritesKey) ?? [];
  }

  Future<void> addFavorite(String vehicleId) async {
    final prefs = await SharedPreferences.getInstance();
    final favorites = prefs.getStringList(_favoritesKey) ?? [];
    if (!favorites.contains(vehicleId)) {
      favorites.add(vehicleId);
      await prefs.setStringList(_favoritesKey, favorites);
    }
  }

  Future<void> removeFavorite(String vehicleId) async {
    final prefs = await SharedPreferences.getInstance();
    final favorites = prefs.getStringList(_favoritesKey) ?? [];
    favorites.remove(vehicleId);
    await prefs.setStringList(_favoritesKey, favorites);
  }

  Future<bool> isFavorite(String vehicleId) async {
    final prefs = await SharedPreferences.getInstance();
    final favorites = prefs.getStringList(_favoritesKey) ?? [];
    return favorites.contains(vehicleId);
  }

  /// Onboarding

  Future<bool> hasSeenOnboarding() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getBool(_onboardingKey) ?? false;
  }

  Future<void> setOnboardingSeen() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(_onboardingKey, true);
  }
}
